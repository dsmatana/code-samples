<?php

namespace App\Traits;

use App\NewsletterTopic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait NewsletterTopicUnsubscribable {
	// Relations
	public function unsubscribedTopics(): MorphToMany {
		return $this->morphToMany(NewsletterTopic::class, 'recipient_model', 'newsletters_topics_unsubscribed', 'recipient_model_id', 'topic_id')
			->withPivot('reason')
			->withPivot('reason_message')
			->withTimestamps();
	}

	// Scopes
	public function scopeOnlySubscribedToTopic(Builder &$qb, NewsletterTopic $topic) {
		$qb->whereDoesntHave('unsubscribedTopics', function (Builder &$qb) use (&$topic) {
			$qb->where('topic_id', $topic->id);
		});
	}
}
