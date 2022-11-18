<?php

namespace App;

use App\Contracts\NewsletterSendable;
use App\Contracts\NewsletterTopicUnsubscribable as NewsletterTopicUnsubscribableContract;
use App\Traits\HasCustomer;
use App\Traits\HasIncludes;
use App\Traits\NewsletterTopicUnsubscribable;
use App\Traits\Transforms;
use App\Transformers\UserTransformer;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements NewsletterSendable, NewsletterTopicUnsubscribableContract {
	use Notifiable, HasCustomer, HasIncludes, NewsletterTopicUnsubscribable, Transforms, SoftDeletes;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'id', 'uuid', 'customer_id', 'name', 'surname', 'email', 'password', 'deleted_at',
	];

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password', 'remember_token',
	];

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = [
		'email_verified_at' => 'datetime',
	];

	protected $transformer = UserTransformer::class;

	// Methods
	public function getCustomerIdField(): string {
		return 'customer_id';
	}
	
	public function getEmailField(): string {
		return 'email';
	}

	public function updateRelationships(Request $request) {
		if ($request->filled('unsubscribed_topics')) {
			$topics = NewsletterTopic::select('id')
				->onlyUnsubscribable()
				->whereIn('id', sync_collection($request->unsubscribed_topics))
				->get();

			newsletters()->unsubscribeSync($this->email, $topics, 'ggtshop_profile', 'ggtshop.sk update from profile');
		}
	}
}
