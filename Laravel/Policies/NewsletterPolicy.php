<?php

namespace App\Policies;

use App\Newsletter;
use App\NewsletterRecipient;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsletterPolicy {
	use HandlesAuthorization;

	/**
	 * Determine whether the user can view any models.
	 *
	 * @param  \App\User  $user
	 * @return mixed
	 */
	public function viewAny(User $user) {
		return $this->checkModuleScope();
	}

	/**
	 * Determine whether the user can view the model.
	 *
	 * @param  \App\User  $user
	 * @param  \App\Newsletter  $newsletter
	 * @return mixed
	 */
	public function view(User $user, Newsletter $newsletter) {
		return $this->checkModuleScope();
	}

	/**
	 * Determine whether the user can create models.
	 *
	 * @param  \App\User  $user
	 * @return mixed
	 */
	public function create(User $user) {
		return $this->checkModuleScope();
	}

	/**
	 * Determine whether the user can update the model.
	 *
	 * @param  \App\User  $user
	 * @param  \App\Newsletter  $newsletter
	 * @return mixed
	 */
	public function update(User $user, Newsletter $newsletter) {
		if (!$this->checkModuleScope() || $newsletter->hasState([Newsletter::STATE_SENDING, Newsletter::STATE_SENT])) {
			return $this->deny('Cannot update newsletter, see NewsletterPolicy.');
		}
		return true;
	}

	/**
	 * Determine whether the user can delete the model.
	 *
	 * @param  \App\User  $user
	 * @param  \App\Newsletter  $newsletter
	 * @return mixed
	 */
	public function delete(User $user, Newsletter $newsletter) {
		if (!$this->checkModuleScope() || $newsletter->hasState([Newsletter::STATE_SENDING, Newsletter::STATE_SENT])) {
			return $this->deny('Cannot delete newsletter, see NewsletterPolicy.');
		}
		return true;
	}

	/**
	 * Determine whether the user can restore the model.
	 *
	 * @param  \App\User  $user
	 * @param  \App\Newsletter  $newsletter
	 * @return mixed
	 */
	public function restore(User $user, Newsletter $newsletter) {
		return $this->checkModuleScope();
	}

	/**
	 * Determine whether the user can permanently delete the model.
	 *
	 * @param  \App\User  $user
	 * @param  \App\Newsletter  $newsletter
	 * @return mixed
	 */
	public function forceDelete(User $user, Newsletter $newsletter) {
		if (!$this->checkModuleScope() || $newsletter->hasState([Newsletter::STATE_SENDING, Newsletter::STATE_SENDING_STOPPED, Newsletter::STATE_SENT])) {
			return $this->deny('Cannot force delete newsletter, see NewsletterPolicy.');
		}
		return true;
	}

	/**
	 * Determine whether the user can export recipients.
	 *
	 * @param \App\User $user
	 * @param Newsletter $newsletter
	 * @return void
	 */
	public function exportRecipients(User $user, Newsletter $newsletter) {
		if (!$this->checkModuleScope()) {
			return $this->deny('Cannot export newsletter recipients, see NewsletterPolicy.');
		}
		return true;
	}

	/**
	 * Determine whether the newsletter can be built.
	 *
	 * @param \App\User $user
	 * @param Newsletter $newsletter
	 * @return void
	 */
	public function build(User $user, Newsletter $newsletter) {
		if (!$this->checkModuleScope() || $newsletter->hasState([Newsletter::STATE_SENDING, Newsletter::STATE_SENDING_STOPPED, Newsletter::STATE_SENT])) {
			return $this->deny('Cannot build newsletter, see NewsletterPolicy.');
		}
		return true;
	}

	/**
	 * Determine whether the newsletter can be send.
	 *
	 * @param \App\User $user
	 * @param Newsletter $newsletter
	 * @return void
	 */
	public function send(User $user, Newsletter $newsletter) {
		if (!$this->checkModuleScope() || $newsletter->hasState([Newsletter::STATE_SENDING, Newsletter::STATE_SENDING_STOPPED, Newsletter::STATE_SENT])) {
			return $this->deny('Cannot send newsletter, see NewsletterPolicy.');
		}
		return true;
	}

	/**
	 * Determine whether the newsletter can be stopped sending.
	 *
	 * @param \App\User $user
	 * @param Newsletter $newsletter
	 * @return void
	 */
	public function sendStop(User $user, Newsletter $newsletter) {
		if (!$this->checkModuleScope() || !$newsletter->hasState([Newsletter::STATE_SENDING])) {
			return $this->deny('Cannot stop sending newsletter, see NewsletterPolicy.');
		}
		return true;
	}

	/**
	 * Determine whether the newsletter can be resumed sending.
	 *
	 * @param \App\User $user
	 * @param Newsletter $newsletter
	 * @return void
	 */
	public function sendResume(User $user, Newsletter $newsletter) {
		if (!$this->checkModuleScope() || !$newsletter->hasState([Newsletter::STATE_SENDING_STOPPED])) {
			return $this->deny('Cannot stop sending newsletter, see NewsletterPolicy.');
		}
		return true;
	}

	/**
	 *  Determine whether the newsletter recipient can increment impressions count on newsletter.
	 *
	 * @param NewsletterRecipient $newsletterRecipient
	 * @param Newsletter $newsletter
	 * @return mixed
	 */
	public function impress(NewsletterRecipient $newsletterRecipient, Newsletter $newsletter) {
		return $newsletterRecipient->newsletter_id == $newsletter->id
			&& !empty($newsletterRecipient->sent_at)
			&& empty($newsletterRecipient->impressed_at);
	}

	/**
	 *  Determine whether the newsletter recipient can increment clicks count on newsletter.
	 *
	 * @param NewsletterRecipient $newsletterRecipient
	 * @param Newsletter $newsletter
	 * @return mixed
	 */
	public function click(NewsletterRecipient $newsletterRecipient, Newsletter $newsletter) {
		return $newsletterRecipient->newsletter_id == $newsletter->id
			&& !empty($newsletterRecipient->sent_at);
	}

	// Helpers

	/**
	 * Checks if oauth token has module scope
	 *
	 * @return bool
	 */
	protected function checkModuleScope() {
		return auth()->hasScope('amos.newsletters'); // Only supports current user
	}
}
