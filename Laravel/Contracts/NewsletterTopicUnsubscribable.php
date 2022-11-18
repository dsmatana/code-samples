<?php

namespace App\Contracts;

use App\NewsletterTopic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface NewsletterTopicUnsubscribable {
	// Relations
	public function unsubscribedTopics(): MorphToMany;

	// Scopes
	public function scopeOnlySubscribedToTopic(Builder &$qb, NewsletterTopic $topic);

	// Methods
	public function getEmailField(): string;
}
