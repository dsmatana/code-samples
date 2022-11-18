<?php

namespace App;

use App\Contracts\NewsletterSendable;
use App\Contracts\NewsletterTopicUnsubscribable as NewsletterTopicUnsubscribableContract;
use App\Traits\HasCustomer;
use App\Traits\NewsletterTopicUnsubscribable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryAddress extends Model implements NewsletterSendable, NewsletterTopicUnsubscribableContract {
	use SoftDeletes, HasCustomer, NewsletterTopicUnsubscribable;
	
	protected $table = 'delivery_addresses';
	public $timestamps = false;
	public $incrementing = false;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'id',
		'customer_id',
		'code',
		'contact_email',
		'deleted_at',
	];

	// Methods
	public function getCustomerIdField(): string {
		return 'customer_id';
	}

	public function getEmailField(): string {
		return 'contact_email';
	}
}
