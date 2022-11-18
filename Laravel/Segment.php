<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Segment extends Model {
	public $timestamps = false;
	public $incrementing = false;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'id',
		'title',
		'visible',
		'enabled',
	];

	protected $casts = [
		'visible' => 'bool',
		'enabled' => 'bool',
	];

	// Relations
	public function customers() {
		return $this->belongsToMany(Customer::class, 'customers_segments', 'segment_id', 'customer_id');
	}

	// Scopes
	public function scopeOnlyVisible(Builder &$qb) {
		$qb->where('visible', true);
	}

	public function scopeOnlyEnabled(Builder &$qb) {
		$qb->where('enabled', true);
	}

	public function scopeFilter(Builder &$qb, Request $request) {
		if ($request->filled('visible') && (int) $request->visible == 1) {
			$qb->onlyVisible();
		}

		if ($request->filled('enabled') && (int) $request->enabled == 1) {
			$qb->onlyEnabled();
		}
	}
}
