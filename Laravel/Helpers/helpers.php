<?php
use Illuminate\Support\Collection;

if (!function_exists('dateTimeAtom')) {
	function dateTimeAtom($datetime, $default = null) {
		return !blank($datetime) ? (new \Carbon\Carbon($datetime))->toAtomString() : $default;
	}
}

if (!function_exists('sync_collection')) {
	function sync_collection($items, $identifier = 'id'): array {
		$mapper = function ($item) use (&$identifier) {
			if (is_object($item)) {
				return $item->{$identifier};
			}
			if (is_array($item)) {
				return $item[$identifier];
			}
			if (is_numeric($item)) {
				return (int) $item;
			}
			return $item;
		};
		if ($items instanceof \Illuminate\Support\Collection) {
			return $items->map($mapper)->all();
		}
		return array_map($mapper, $items);
	}
}

if (!function_exists('item_or_items')) {
	function item_or_items($item): Collection {
		return collect(Arr::wrap($item));
	}
}
