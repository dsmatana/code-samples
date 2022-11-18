<?php

namespace App\Http\Requests;

use App\Newsletter;
use App\NewsletterTopic;
use App\Rules\ObjectWithId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsletterUpdate extends FormRequest {
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize() {
		return true; // Handled by Policies\NewsletterPolicy
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules() {
		return [
			'topic_id'           => ['exists:newsletters_topics,id'],
			'title'              => ['string', 'max:100'],
			'subject'            => ['string', 'min:3'],
			'segments_aggregate' => [Rule::in(Newsletter::SEG_AGGR)],
			'send_at'            => ['nullable', 'date', 'after:now'],
			'customers'          => ['array'],
			'customers.*'        => [new ObjectWithId],
			'segments'           => ['array'],
			'segments.*'         => [new ObjectWithId],
			'pricelists'         => ['array'],
			'pricelists.*'       => [new ObjectWithId],
			'content'            => ['string', 'nullable'],
		];
	}
}
