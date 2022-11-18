<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class NewsletterTemplateTransformer extends TransformerAbstract {
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
	public function transform($model) {
		return [
			'id'    => $model->id,
			'title' => $model->title,
			'html'  => $model->html,
			'icon'  => $model->icon,
		];
	}
}
