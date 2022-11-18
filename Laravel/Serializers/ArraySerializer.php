<?php

namespace App\Serializers;

use League\Fractal\Serializer\ArraySerializer as FractalArraySerializer;

class ArraySerializer extends FractalArraySerializer {
	/**
	* Serialize a collection.
	*
	* @param string $resourceKey
	* @param array  $data
	*
	* @return array
	*/
	public function collection(?string $resourceKey, array $data): array {
		if ($resourceKey) {
			return [$resourceKey => $data];
		}
		return $data;
	}

	/**
	 * Serialize null resource.
	 *
	 * @return array
	 */
	public function null(): ?array {
		return null;
	}
}
