<?php

namespace App\Events;

use App\Newsletter;
use App\Transformers\NewsletterTransformer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NewsletterEvent implements ShouldBroadcastNow {
	use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * Newsletter instance
	 *
	 * @var Newsletter
	 */
	protected Newsletter $newsletter;

	/**
	 * Method
	 *
	 * @var string
	 */
	protected string $method;

	/**
	 * Action
	 *
	 * @var string
	 */
	protected ?string $action;

	/**
	 * Transform includes
	 *
	 * @var Collection
	 */
	protected $includes = [
		'topic',
		'customers',
		'segments',
		'pricelists',
		'attachments',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	/**
	 * Create a new event instance.
	 *
	 * @param Newsletter $newsletter
	 * @param string $action
	 */
	public function __construct(Newsletter $newsletter, string $method, ?string $action = null) {
		$this->newsletter = $newsletter;
		$this->method = $method;
		$this->action = $action;
		$this->includes = collect($this->includes);
	}

	/**
	 * Set transformer includes
	 *
	 * @param array $includes
	 * @return self
	 */
	public function withIncludes(...$includes) {
		$this->includes = collect($includes)->flatten();
		return $this;
	}

	/**
	 * Get the channels the event should broadcast on.
	 *
	 * @return \Illuminate\Broadcasting\Channel|array
	 */
	public function broadcastOn() {
		return new PrivateChannel('newsletters');
	}

	/**
	* The event's broadcast name.
	*
	* @return string
	*/
	public function broadcastAs() {
		return $this->method;
	}

	/**
	 * Get the data to broadcast.
	 *
	 * @return array
	 */
	public function broadcastWith() {
		$allIncludes = app(NewsletterTransformer::class)->getAvailableIncludes();
		$excludes = array_diff($allIncludes, $this->includes->all());
		return [
			'action' => $this->action,
			'data'   => $this->newsletter->transform($this->includes->all(), $excludes)->toArray(),
		];
	}
}
