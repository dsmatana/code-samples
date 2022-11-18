<?php
namespace App\Services;

use App\Customer;
use App\Mail\NewslettersRecipientUnsubscribed;
use App\NewsletterRecipient;
use App\NewsletterTopic;
use Illuminate\Log\Logger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Log;

class Newsletters {
	/**
	 * Logger
	 *
	 * @var Logger
	 */
	protected $log;

	public function __construct() {
		$this->log = Log::channel('newsletters');
	}

	/**
	 * Get logger
	 *
	 * @return Logger
	 */
	public function log() {
		return $this->log;
	}

	/**
	 * Sync unsubscribed topics by email
	 * IMPORTANT: Not checking topic unsubscribable flag
	 *
	 * @param array|Collection $topics
	 * @param string $email
	 * @return void
	 */
	public function unsubscribeSync(string $email, $topics, $reason = null, $reason_message = null) {
		if ($topics instanceof NewsletterTopic) {
			$topics = collect([$topics]);
		}

		$allAudiences = NewsletterTopic::select('audience')->get()->pluck('audience')->flatten()->unique();

		foreach ($allAudiences as $audienceModelClass) {
			$models = $audienceModelClass::where(app($audienceModelClass)->getEmailField(), $email)->get();

			foreach ($models as $model) {
				$syncData = $topics->pluck('id')
						->reduce(function ($result, $topicId) use (&$model, &$reason, &$reason_message) {
							$result[$topicId] = [
								'email'          => $model->{$model->getEmailField()},
								'reason'         => $reason,
								'reason_message' => $reason_message,
							];
		
							return $result;
						}, []);

				$model->unsubscribedTopics()->sync($syncData);
			}
		}
	}

	/**
	 * Send notification after unsubscribe
	 *
	 * @todo relocate
	 * @param NewsletterRecipient $newsletterRecipient
	 * @return void
	 */
	public function recipientUnsubscribedNotify(NewsletterRecipient $newsletterRecipient) {
		$emails = collect(explode(',', config('newsletters.unsubscribe_notify')))->trim()->all();

		if (count($emails)) {
			Mail::queue(new NewslettersRecipientUnsubscribed($newsletterRecipient, $emails));
		} else {
			$this->log->warning('No unsubscribe notification recipients');
		}
	}
}
