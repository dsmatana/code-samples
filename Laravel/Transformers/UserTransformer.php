<?php

namespace App\Transformers;

use App\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract {
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
		'unsubscribed_topics',
	];
	
	/**
	 * A Fractal transformer.
	 *
	 * @return array
	 */
	public function transform(User $model) {
		return [
			'id'     => $model->id,
			'uuid'   => $model->uuid,
			'email'  => $model->email,
		];
	}

	protected function includeUnsubscribedTopics(User $model) {
		return $this->collection($model->unsubscribedTopics, new NewsletterTopicTransformer);
	}
}
