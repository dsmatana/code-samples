<?php

namespace App\Jobs;

use App\Events\NewsletterEvent;
use App\Mail\NewsletterMail;
use App\Newsletter;
use App\NewsletterRecipient;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Mail;

class NewsletterSend implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * Newsletter
	 *
	 * @var Newsletter
	 */
	protected Newsletter $newsletter;

	/**
	 * Broadcast events
	 *
	 * @var Collection
	 */
	protected Collection $broadcasts;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(Newsletter $newsletter) {
		$this->newsletter = $newsletter;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle() {
		newsletters()->log()->info('Sending newsletter', ['newsletter' => $this->newsletter->id, 'title' => $this->newsletter->title]);

		if ($this->newsletter->state == Newsletter::STATE_SENT) {
			return newsletters()->log()->warning('Newsletter already sent, exiting');
		}

		if (!$this->newsletter->send_start_at) {
			newsletters()->log()->info('Marking newsletter as sending');
			$this->newsletter->update(['send_start_at' => now()]);

			// Send start event
			if ($this->broadcasts->contains('start')) {
				broadcast(new NewsletterEvent($this->newsletter, 'update', 'send.start'));
			}
		}

		if ($this->newsletter->send_stop_at) {
			$this->newsletter->update(['send_stop_at' => null]);
			// Send resume event
			if ($this->broadcasts->contains('resume')) {
				broadcast(new NewsletterEvent($this->newsletter, 'update', 'send.resume'));
			}
		}

		// TODO: optimize iteration for large data set
		$recipientCount = NewsletterRecipient::where('newsletter_id', $this->newsletter->id)->count();

		newsletters()->log()->info('All recipients:    '.$recipientCount);

		$recipients = NewsletterRecipient::where('newsletter_id', $this->newsletter->id)->whereNull('sent_at')->get();

		newsletters()->log()->info('Unsent recipients: '.$recipients->count());

		/** @var \Illuminate\Database\Eloquent\Collection $recipients */

		if ($recipients->isEmpty()) {
			return newsletters()->log()->info('No recipients, exiting');
		}
		
		foreach ($recipients as $recipient) {
			$logData = [
				'uuid'  => $recipient->uuid,
				'email' => $recipient->email,
			];

			newsletters()->log()->debug('Sending newsletter', $logData);

			try {
				Mail::send(new NewsletterMail($this->newsletter, $recipient));
				$recipient->update(['sent_at' => now()]);
			} catch (Exception $e) {
				$recipient->update(['error' => $e->getMessage()]);
				newsletters()->log()->error('Send error: '.$e->getMessage(), $logData);
			} finally {
				$stoppedAt = Newsletter::select('send_stop_at')->whereId($this->newsletter->id)->first()->send_stop_at;

				if ($stoppedAt) {
					return newsletters()->log()->info('Sending stopped at `'.$stoppedAt.'`, exiting');
				}

				// TODO: fix progress on frontend
				// if ($this->broadcasts->contains('progress')) {
				// 	broadcast((new NewsletterEvent($this->newsletter, 'update', 'send.progress'))->withIncludes('stats'));
				// }
			}
		}

		newsletters()->log()->info('Marking newsletter as sent');
		$this->newsletter->update(['sent_at' => now()]);

		// Send finish event
		if ($this->broadcasts->contains('finish')) {
			broadcast(new NewsletterEvent($this->newsletter, 'update', 'send.finish'));
		}
		
		newsletters()->log()->info('Sending done');
	}

	/**
	 * Add broadcasts. Values: start, finish
	 *
	 * @return self
	 */
	public function withBroadcasts(...$events) {
		$this->broadcasts = collect($events)->flatten();
		return $this;
	}
}
