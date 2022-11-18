<?php

namespace App\Transformers;

use App\NewsletterTopic;
use League\Fractal\TransformerAbstract;

class NewsletterTopicTransformer extends TransformerAbstract {
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
		//
	];
	
	/**
	 * A Fractal transformer.
	 *
	 * @return array
	 */
	public function transform(NewsletterTopic $model) {
		return [
			'id'             => $model->id,
			'uuid'           => $model->uuid,
			'name'           => $model->name,
			'title'          => $model->title,
			'unsubscribable' => $model->unsubscribable,
		];
	}
}
