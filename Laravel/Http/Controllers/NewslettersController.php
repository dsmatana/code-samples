<?php

namespace App\Http\Controllers;

use App\Events\NewsletterEvent;
use App\Newsletter;
use App\NewsletterRecipient;
use App\NewsletterTopic;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;

class NewslettersController extends Controller {

	/**
	 * View newsletter
	 *
	 * @param Newsletter $newsletter
	 * @param Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function view(Newsletter $newsletter, Request $request) {
		if (!$newsletter->exists) {
			return abort(404);
		}

		$recipient = null;
		if ($request->has('recipient') && Uuid::isValid($request->recipient)) {
			$recipient = NewsletterRecipient::whereUuid($request->recipient)->first();
		}

		return $newsletter->view($recipient);
	}

	/**
	 * Newsletter impression handler
	 *
	 * @param Newsletter $newsletter
	 * @param Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function impress(Newsletter $newsletter, Request $request) {
		if ($request->has('recipient')
			&& Uuid::isValid($request->recipient)
			&& $recipient = NewsletterRecipient::whereUuid($request->recipient)->first()
		) {
			if ($recipient->can('impress', $newsletter)) {
				$recipient->update(['impressed_at' => now()]);
				$newsletter->increment('impressions_count');
				broadcast(new NewsletterEvent($newsletter, 'update'));
			}
		}

		$response = response()->file(Storage::disk('newsletters')->path('newsletter-logo.png'), [
			'Cache-Control' => 'no-cache',
		]);
		ob_end_clean();

		return $response;
	}

	/**
	 * Newsletter click handler
	 *
	 * @param Newsletter $newsletter
	 * @param Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function click(Newsletter $newsletter, Request $request) {
		if ($request->has('recipient') && $recipient = NewsletterRecipient::whereUuid($request->recipient)->first()) {
			if ($recipient->can('click', $newsletter)) {
				$recipient->update(['last_click_at' => now()]);
				$recipient->increment('clicks_count');
				$newsletter->increment('clicks_count');
				broadcast(new NewsletterEvent($newsletter, 'update'));
			}
		}
		
		if ($request->has('href')) {
			return redirect($request->href);
		}

		return abort(404);
	}

	/**
	 * Newsletter unsubscribe form
	 *
	 * @param NewsletterRecipient $newsletter
	 * @return \Illuminate\Http\Response
	 */
	public function unsubForm(NewsletterRecipient $newsletterRecipient) {
		if (!$newsletterRecipient->exists) {
			return abort(404);
		}

		return response()->view('newsletters.unsubscribeForm', [
			'recipient_uuid' => $newsletterRecipient->uuid,
			'topics'         => NewsletterTopic::all(),
			'reasons'        => Newsletter::UNSUB_REASONS,
		]);
	}

	/**
	 * Newsletter unsubscribe form store
	 *
	 * @param Request $request
	 * @param NewsletterRecipient $newsletter
	 * @return \Illuminate\Http\Response
	 */
	public function unsubFormStore(Request $request, NewsletterRecipient $newsletterRecipient) {
		$this->validate($request, [
			// 'topics'         => ['array'],
			// 'topics.*'       => ['uuid', 'exists:newsletters_topics,uuid'],
			'reason'         => ['required', Rule::in(array_keys(Newsletter::UNSUB_REASONS))],
			'reason_message' => ['required_if:reason,other'],
		], [
			'reason.required' => 'Vyberte dôvod odhlásenia',
			'reason_message.required_if' => 'Prosím popíšte dôvod odhlásenia',
		]);

		$response = redirect()->back();

		try {
			$topics = NewsletterTopic::onlyUnsubscribable()
				// ->whereIn('uuid', $request->topics) // All unsubscribable topics for now
				->get();
			;

			$audience = $topics->pluck('audience')->flatten()->unique();

			foreach ($audience as $modelClass) {
				$models = $modelClass::where(app($modelClass)->getEmailField(), $newsletterRecipient->email)->get();

				foreach ($models as $model) {
					$syncData = $topics->pluck('id')
						->reduce(function ($result, $id) use (&$model, &$request) {
							$result[$id] = [
								'email'          => $model->{$model->getEmailField()},
								'reason'         => $request->reason,
								'reason_message' => $request->reason_message,
							];
		
							return $result;
						}, []);

					$model->unsubscribedTopics()->sync($syncData);
				}
			}

			newsletters()->recipientUnsubscribedNotify($newsletterRecipient);
		} catch (Exception $e) {
			newsletters()->log()->error('Unsubscribe error: '.$e->getMessage());
			return $response->withErrors(['error' => 'Nastala chyba, kontaktujte prosím prevádzkovateľa.']);
		}

		return $response->with('success', 'Odhlásenie prebehlo úspešne');
	}
}
