<?php

namespace App;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class NewsletterRecipient extends Model implements AuthorizableContract {
	use HasUuid, Authorizable;
	
	protected $table = 'newsletters_recipients';

	protected $primaryKey = 'uuid';

	public $incrementing = false;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'newsletter_id',
		'email',
		'sent_at',
		'error',
		'impressed_at',
		'last_click_at',
	];

	// Relations
	public function recipient() {
		return $this->morphTo('recipient_model');
	}
}
