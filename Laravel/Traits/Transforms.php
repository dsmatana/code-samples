<?php

namespace App\Traits;

use RuntimeException;
use Spatie\Fractal\Fractal;

trait Transforms {
	/**
	 * Transform object with fractal
	 *
	 * @param array $includes
	 * @param array $excludes
	 * @param string $resourceName
	 * @return void
	 */
	public function transform($includes = [], $excludes = [], $resourceName = 'data'): Fractal {
		$transformerClass = $this->transformer;

		if (!$transformerClass) {
			throw new RuntimeException('Class `'.get_class($this).'` doesn\'t have transformer property');
		}

		$transformerInstance = app($transformerClass);

		if ($includes === '*' && !request()->has('include')) {
			$includes = $transformerInstance->getAvailableIncludes();
		}

		if ($excludes === '*') {
			$excludes = $transformerInstance->getAvailableIncludes();
		}

		return fractal()
			->item($this)
			->transformWith($transformerInstance)
			->parseIncludes($includes)
			->parseExcludes($excludes)
			->withResourceName($resourceName)
		;
	}
}
