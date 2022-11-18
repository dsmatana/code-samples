<?php

namespace App\Jobs;

use App\Contracts\NewsletterSendable;
use App\Customer;
use App\Events\NewsletterEvent;
use App\Newsletter;
use App\NewsletterRecipient;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class NewsletterBuild implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	const AGGR_OPERANDS = [
		Newsletter::SEG_AGGR_UNION => 'OR',
		Newsletter::SEG_AGGR_INTERSECT => 'AND',
	];

	/**
	 * Newsletter
	 *
	 * @var Newsletter
	 */
	protected Newsletter $newsletter;

	/**
	 * Remove existing recipients
	 *
	 * @var bool
	 */
	protected bool $fresh;

	/**
	 * Don't check states
	 *
	 * @var boolean
	 */
	protected bool $force = false;

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
	public function __construct(Newsletter $newsletter, bool $fresh = false) {
		$this->newsletter = $newsletter;
		$this->fresh = $fresh;
		$this->broadcasts = collect();
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle() {
		newsletters()->log()->info('Building newsletter recipients', ['newsletter' => $this->newsletter->id, 'title' => $this->newsletter->title]);
		try {
			DB::beginTransaction();

			// Check state
			if (!$this->force && $this->newsletter->state == Newsletter::STATE_SENT) {
				return newsletters()->log()->warning('Newsletter sent, cannot build');
			}

			// Check state
			if (!$this->force && $this->newsletter->build_state == Newsletter::BUILD_STATE_BUILDING) {
				return newsletters()->log()->warning('Already building, exiting');
			}

			// Check correct aggregation method
			if (!array_key_exists($this->newsletter->segments_aggregate, self::AGGR_OPERANDS)) {
				throw new Exception("Unknown aggregation method: `{$this->newsletter->segments_aggregate}` Permitted: ".implode(', ', Newsletter::SEG_AGGR));
			}

			// Set state to "building"
			$this->newsletter->update(['recipient_count' => -1]);

			// Send start event
			if ($this->broadcasts->contains('start')) {
				broadcast(new NewsletterEvent($this->newsletter, 'update', 'build.start'));
			}

			// Delete existing
			if ($this->fresh) {
				newsletters()->log()->info('Removing existing recipients');
				NewsletterRecipient::getQuery()->whereNewsletterId($this->newsletter->id)->delete();
			}
			
			$totalInserted = 0;

			// Build recipient list
			foreach ($this->newsletter->topic->audience as $model) {
				if (!class_exists($model)) {
					throw new Exception("Audience class `{$model}` does not exist.");
				}

				$model = app($model);
				$modelClass = get_class($model);
				$modelClassEscaped = str_replace('\\', '\\\\', $modelClass);

				// Get customer id column name
				if ($model instanceof Customer) {
					$customerIdCol = $model->getKeyName(); //id
				} elseif ($model instanceof NewsletterSendable) {
					$customerIdCol = $model->getCustomerIdField();
				} else {
					throw new Exception("Audience class `{$modelClass}` must implement ".NewsletterSendable::class);
				}

				// Glue-up aggregation conditions by segmentation
				$this->newsletter->load(['customers', 'segments', 'pricelists']);

				$aggrConditions = [];

				if (!$this->newsletter->segments->isEmpty()) {
					$aggrConditions[] = 'ns.newsletter_id IS NOT NULL';
				}

				if (!$this->newsletter->pricelists->isEmpty()) {
					$aggrConditions[] = 'np.newsletter_id IS NOT NULL';
				}

				$aggrOperand = self::AGGR_OPERANDS[$this->newsletter->segments_aggregate];
				$aggrConditions = count($aggrConditions) ? implode(" {$aggrOperand} ", $aggrConditions) : '0';

				// Hardcoded first condition (by newsletters_customers) to prevent sending to all valid recipients
				$aggrCondition = "AND (nc.newsletter_id IS NOT NULL OR ({$aggrConditions}))";

				// dump($modelClass);
				// dump($aggrCondition);

				$result = DB::select("
					SELECT
						rcp.{$model->getKeyName()} as id,
						rcp.{$model->getEmailField()} as email
						-- nc.customer_id,
						-- ns.segment_id,
						-- np.pricelist_id,
						-- IF(nc.customer_id IS NOT NULL, 'customer', IF(ns.segment_id IS NOT NULL, 'segment', IF(np.pricelist_id IS NOT NULL, 'pricelist', null))) as source,
						-- COALESCE(nc.customer_id, ns.segment_id, np.pricelist_id) AS source_model_id
					FROM {$model->getTable()} rcp
					LEFT JOIN customers_segments               cs ON cs.customer_id  = rcp.{$customerIdCol}
					LEFT JOIN customers_pricelists             cp ON cp.customer_id  = rcp.{$customerIdCol}
					LEFT JOIN newsletters_customers            nc ON nc.customer_id  = rcp.{$customerIdCol} AND nc.newsletter_id = {$this->newsletter->id}
					LEFT JOIN newsletters_segments             ns ON ns.segment_id   = cs.segment_id        AND ns.newsletter_id = {$this->newsletter->id}
					LEFT JOIN newsletters_pricelists           np ON np.pricelist_id = cp.pricelist_id      AND np.newsletter_id = {$this->newsletter->id}
					LEFT JOIN newsletters_topics_unsubscribed ntu ON 
						ntu.topic_id = {$this->newsletter->topic_id} 
						AND ntu.recipient_model_type = '{$modelClassEscaped}'
						AND ntu.recipient_model_id = rcp.id 
					WHERE 1
						AND rcp.deleted_at IS NULL                             -- Exclude deleted
						AND rcp.{$model->getEmailField()} IS NOT NULL          -- Only with filled emails
						AND ntu.recipient_model_id IS NULL                     -- Exclude unsubscribed
						AND rcp.{$customerIdCol} != 3                          -- Exclude `not logged in` customer
						{$aggrCondition}                                       -- Aggregation conditions via segmentation
					GROUP BY rcp.id
				");

				// dump($result);
				// continue;

				newsletters()->log()->info('Recipient count of audience `'.$modelClass.'`: '.count($result));

				if (!count($result)) {
					continue;
				}

				// Filter out invalid emails and prepare data for insert
				$recipients = collect($result)->filter(function ($item) use (&$modelClass) {
					if (!filter_var($item->email, FILTER_SANITIZE_EMAIL)) {
						newsletters()->log()->info("Invalid email address `{$item->email}` of model class `{$modelClass} with id {$item->id}");
						return false;
					}
					return true;
				})->map(function ($item) use (&$modelClass) {
					return [
						'uuid'                 => (string) Uuid::uuid4(),
						'newsletter_id'        => $this->newsletter->id,
						'email'                => $item->email,
						'recipient_model_type' => $modelClass,
						'recipient_model_id'   => $item->id,
						'created_at'           => now()->format('Y-m-d H:i:s.u0'),
					];
				});

				$inserted = NewsletterRecipient::insertOrIgnore($recipients->all());

				$totalInserted += $inserted;

				newsletters()->log()->info('Inserted '.$inserted.' recipients of audience `'.$modelClass.'`');
			}

			$this->newsletter->update(['recipient_count' => $totalInserted]);

			DB::commit();
			newsletters()->log()->info('Build done');

			// Send finish event
			if ($this->broadcasts->contains('finish')) {
				broadcast(new NewsletterEvent($this->newsletter, 'update', 'build.finish'));
			}
		} catch (Exception $e) {
			DB::rollBack();
			$this->newsletter->update(['recipient_count' => null]);
			newsletters()->log()->error($e->getMessage());

			// Send error event
			if ($this->broadcasts->contains('error')) {
				broadcast(new NewsletterEvent($this->newsletter, 'update', 'build.error'));
			}

			throw $e;
		}
	}

	/**
	 * Add broadcasts. Values: start, finish, error
	 *
	 * @return self
	 */
	public function withBroadcasts(...$events) {
		$this->broadcasts = collect($events)->flatten();
		return $this;
	}

	/**
	 * Don't check states
	 *
	 * @return self
	 */
	public function force() {
		$this->force = true;
		return $this;
	}
}
