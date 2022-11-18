<?php

namespace App\Transformers;

use App\NewsletterContent;
use League\Fractal\TransformerAbstract;

class NewsletterContentTransformer extends TransformerAbstract {
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
	public function transform(NewsletterContent $model) {
		return [
			'id'         => $model->id,
			'html'       => $model->html,
			'created_at' => dateTimeAtom($model->created_at),
		];
	}
}
