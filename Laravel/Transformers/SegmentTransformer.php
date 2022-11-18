<?php

namespace App\Transformers;

use App\Segment;
use League\Fractal\TransformerAbstract;

class SegmentTransformer extends TransformerAbstract {
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
	public function transform(Segment $model) {
		return [
			'id'      => $model->id,
			'title'   => $model->title,
			'visible' => $model->visible,
			'enabled' => $model->enabled,
		];
	}
}
