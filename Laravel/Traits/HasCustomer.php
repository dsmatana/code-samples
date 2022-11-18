<?php

namespace App\Traits;

use App\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

trait HasCustomer {
	// Relations
	public function customer(): BelongsTo {
		return $this->belongsTo(Customer::class);
	}

	// Scopes
	public function scopeCustomer(Builder &$qb, $customer) {
		$qb->where('customer_id', $customer);
	}

	public function scopeCustomers(Builder &$qb, $customers) {
		$qb->whereIn('customer_id', $customers);
	}
}
