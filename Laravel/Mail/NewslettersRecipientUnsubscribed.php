<?php

namespace App\Mail;

use App\Customer;
use App\DeliveryAddress;
use App\Newsletter;
use App\NewsletterRecipient;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewslettersRecipientUnsubscribed extends Mailable {
	use Queueable, SerializesModels;

	/**
	 * Recipient
	 *
	 * @var NewsletterRecipient
	 */
	protected $newsletterRecipient;

	/**
	 * Morphed recipient model
	 *
	 * @var Model
	 */
	protected $recipient;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct(NewsletterRecipient $newsletterRecipient, array $emails) {
		$this->newsletterRecipient = $newsletterRecipient;
		$this->subject('Adresát sa odhlásil z newslettra');

		foreach ($emails as $email) {
			$this->to($email);
		}

		$this->recipient = $this->newsletterRecipient->recipient;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build() {
		$unsubscribeRecord = $this->recipient->unsubscribedTopics->first()->pivot;

		if ($this->recipient instanceof Customer) {
			$customer = $this->recipient->company_name;
		} elseif (method_exists($this->recipient, 'customer')) {
			$customer = $this->recipient->customer->company_name;
		} else {
			$customer = null;
		}
		
		switch (get_class($this->recipient)) {
			case Customer::class: $recipientType = 'Zákazník'; break;
			case DeliveryAddress::class:   $recipientType = 'Dodacia adresa'; break;
			case User::class:              $recipientType = 'Používateľ'; break;
			default:                       $recipientType = null;
		}

		return $this->view('newsletters.emails.recipientUnsubscribed', [
			'subject'        => $this->subject,
			'customer'       => $customer,
			'recipient_type' => $recipientType,
			'email'          => $this->newsletterRecipient->email,
			'reason'         => Newsletter::UNSUB_REASONS[$unsubscribeRecord->reason],
			'reason_message' => nl2br(filter_var($unsubscribeRecord->reason_message, FILTER_SANITIZE_FULL_SPECIAL_CHARS)) ?: null,
		]);
	}
}
