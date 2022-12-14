<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ObjectWithId implements Rule {
	/**
	 * Create a new rule instance.
	 *
	 * @return void
	 */
	public function __construct() {
		//
	}

	/**
	 * Determine if the validation rule passes.
	 *
	 * @param  string  $attribute
	 * @param  mixed  $value
	 * @return bool
	 */
	public function passes($attribute, $value) {
		return is_array($value) && isset($value['id']) && !empty($value['id']) && is_numeric($value['id']) && (int) $value['id'] > 0;
	}

	/**
	 * Get the validation error message.
	 *
	 * @return string
	 */
	public function message() {
		return 'The :attribute must be object with numeric `id` property.';
	}
}
