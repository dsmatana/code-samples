<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewsletterContent extends Model {
	protected $table = 'newsletters_content';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'html',
	];
}
