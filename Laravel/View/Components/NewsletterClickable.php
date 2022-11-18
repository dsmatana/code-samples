<?php

namespace App\View\Components;

use App\Newsletter;
use App\NewsletterRecipient;
use Illuminate\View\Component;

class NewsletterClickable extends Component {

	/**
	 * Redirect url
	 *
	 * @var string
	 */
	protected $href;

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
	public function __construct(string $href, string $newsletter, string $recipient = null) {
		$this->href = $href;
		$this->newsletter_uuid = $newsletter;
		$this->recipient_uuid = $recipient;
	}

	/**
	 * Get the view / contents that represent the component.
	 *
	 * @return \Illuminate\View\View|string
	 */
	public function render() {
		return view('components.newsletter-clickable', [
			'url' => route('newsletters.click', [
				'newsletter_uuid' => $this->newsletter_uuid,
				'recipient'       => $this->recipient_uuid,
				'href'            => $this->href,
			]),
		]);
	}
}
