<?php

namespace App\Transformers;

use App\PricelistListModel;
use App\Segment;
use League\Fractal\TransformerAbstract;

class PricelistTransformer extends TransformerAbstract {
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
	public function transform(PricelistListModel $model) {
		return [
			'id'      => $model->id,
			'code'    => $model->code,
			'title'   => $model->title,
			'visible' => $model->visible,
			'enabled' => $model->enabled,
		];
	}
}
