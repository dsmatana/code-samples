<?php

namespace App\View\Components;

use Illuminate\View\Component;

class NewsletterViewAnchor extends Component {
	/**
	 * Newsletter UUID
	 *
	 * @var string
	 */
	protected $newsletter_uuid;

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
	public function __construct(string $newsletter, string $recipient = null) {
		$this->newsletter_uuid = $newsletter;
		$this->recipient_uuid = $recipient;
	}

	/**
	 * Get the view / contents that represent the component.
	 *
	 * @return \Illuminate\View\View|string
	 */
	public function render() {
		return view('components.newsletter-view-anchor', [
			'url' => route('newsletters.view', [
				'newsletter_uuid' => $this->newsletter_uuid,
				'recipient'       => $this->recipient_uuid,
			]),
		]);
	}
}
