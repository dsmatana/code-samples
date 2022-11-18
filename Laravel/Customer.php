<?php

namespace App;

use App\Contracts\NewsletterTopicUnsubscribable as NewsletterTopicUnsubscribableContract;
use App\Traits\NewsletterTopicUnsubscribable;
use App\Traits\Segmentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model implements NewsletterTopicUnsubscribableContract {
	use SoftDeletes, Segmentable, NewsletterTopicUnsubscribable;
	
	public $timestamps = false;
	public $incrementing = false;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'id',
		'code',
		'company_name',
		'contact_email',
		'deleted_at',
	];

	// Methods
	public function getEmailField(): string {
		return 'contact_email';
	}
}
