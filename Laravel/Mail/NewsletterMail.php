<?php

namespace App\Mail;

use App\Newsletter;
use App\NewsletterRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable {
	use Queueable, SerializesModels;

	/**
	 * Newsletter
	 *
	 * @var Newsletter
	 */
	protected Newsletter $newsletter;

	/**
	 * Recipient
	 *
	 * @var NewsletterRecipient
	 */
	protected ?NewsletterRecipient $recipient;

	/**
	 * Custom emails
	 *
	 * @var array
	 */
	protected array $emails = [];

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct(Newsletter $newsletter, NewsletterRecipient $recipient = null, array $emails = []) {
		$this->newsletter = $newsletter;
		$this->recipient = $recipient;
		$this->subject($newsletter->subject);

		if ($recipient) {
			$this->to($recipient->email);
		}

		foreach ($emails as $email) {
			$this->to($email);
		}
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build() {
		$result = $this->view('newsletters.template', $this->newsletter->getViewData($this->recipient));

		foreach ($this->newsletter->attachments as $attachment) {
			$result->attach($attachment->getPath(), [ 'as' => $attachment->name.'.'.$attachment->extension, ]);
		}

		return $result;
	}
}
