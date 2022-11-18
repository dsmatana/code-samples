<?php

namespace App\View\Components;

use Illuminate\View\Component;

class NewsletterUnsubscriber extends Component {
	/**
	 * Recipient UUID
	 *
	 * @var string
	 */
	protected $recipient_uuid;

	/**
	 * Create a new component instance.
	 *
	 * @return void
	 */
	public function __construct(string $recipient) {
		$this->recipient_uuid = $recipient;
	}

	/**
	 * Get the view / contents that represent the component.
	 *
	 * @return \Illuminate\View\View|string
	 */
	public function render() {
		return view('components.newsletter-unsubscriber', [
			'url' => route('newsletters.unsubscribe_form', [
				'newsletter_recipient_uuid' => $this->recipient_uuid,
			]),
		]);
	}
}
