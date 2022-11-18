<?php

namespace App\Transformers;

use App\Newsletter;
use App\Transformers\Traits\HasByIncludes;
use League\Fractal\TransformerAbstract;

class NewsletterTransformer extends TransformerAbstract {
	use HasByIncludes;
	
	/**
	 * List of resources to automatically include
	 *
	 * @var array
	 */
	protected array $defaultIncludes = [
		//
	];
	
	/**
	 * List of resources possible to include
	 *
	 * @var array
	 */
	protected array $availableIncludes = [
		'topic',
		'content',
		'content_history',
		'customers',
		'segments',
		'pricelists',
		'attachments',
		'created_by',
		'updated_by',
		'deleted_by',
	];
	
	/**
	 * A Fractal transformer.
	 *
	 * @return array
	 */
	public function transform(Newsletter $model) {
		return [
			'id'                 => $model->id,
			'uuid'               => $model->uuid,
			'topic_id'           => $model->topic_id,
			'state'              => $model->state,
			'build_state'        => $model->build_state,
			'title'              => $model->title,
			'subject'            => $model->subject,
			'segments_aggregate' => $model->segments_aggregate,
			'recipient_count'    => $model->recipient_count,
			'impressions_count'  => $model->impressions_count,
			'clicks_count'       => $model->clicks_count,
			'send_at'            => dateTimeAtom($model->send_at),
			'sent_at'            => dateTimeAtom($model->sent_at),
			'created_at'         => dateTimeAtom($model->created_at),
			'updated_at'         => dateTimeAtom($model->updated_at),
			'deleted_at'         => dateTimeAtom($model->deleted_at),
		];
	}

	public function includeTopic(Newsletter $model) {
		return $this->item($model->topic, new NewsletterTopicTransformer);
	}

	public function includeContent(Newsletter $model) {
		return $model->content ? $this->item($model->content, new NewsletterContentTransformer) : $this->null();
	}

	public function includeContentHistory(Newsletter $model) {
		return $this->collection($model->contentHistory, new NewsletterContentTransformer);
	}

	public function includeCustomers(Newsletter $model) {
		return $this->collection($model->customers, new CustomerTransformer);
	}

	public function includeSegments(Newsletter $model) {
		return $this->collection($model->segments, new SegmentTransformer);
	}

	public function includePricelists(Newsletter $model) {
		return $this->collection($model->pricelists, new PricelistTransformer);
	}

	public function includeAttachments(Newsletter $model) {
		return $this->collection($model->attachments, new MediaTransformer);
	}
}
