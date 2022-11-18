<?php

namespace App;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsletterTopic extends Model {
	use HasUuid;
	
	protected $table = 'newsletters_topics';
	public $timestamps = false;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'name',
		'title',
		'unsubscribable',
		'audience',
	];

	protected $casts = [
		'audience' => 'array',
	];

	// Scopes
	public function scopeOnlyUnsubscribable(Builder &$qb) {
		$qb->where('unsubscribable', true);
	}
}
