<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface NewsletterSendable {
	// Relations
	public function customer(): BelongsTo;

	// Methods
	public function getCustomerIdField(): string;
	
	public function getEmailField(): string;
}
