<?php

namespace App\Transformers;

use App\Customer;
use League\Fractal\TransformerAbstract;

class CustomerTransformer extends TransformerAbstract {
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
	public function transform(Customer $model) {
		return [
			'id'            => $model->id,
			'code'          => $model->code,
			'company_name'  => $model->company_name,
			'contact_email' => $model->contact_email,
		];
	}
}
