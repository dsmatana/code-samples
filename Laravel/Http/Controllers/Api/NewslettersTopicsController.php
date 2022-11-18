<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\NewsletterTopic;
use App\Transformers\NewsletterTopicTransformer;
use Illuminate\Http\Request;

class NewslettersTopicsController extends Controller {
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index() {
		$items = NewsletterTopic::all();
		return fractal($items)
			->transformWith(new NewsletterTopicTransformer)
			->withResourceName('data')
			->respond();
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		abort(501, 'Not Implemented');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\NewsletterTopic  $newsletterTopic
	 * @return \Illuminate\Http\Response
	 */
	public function show(NewsletterTopic $newsletterTopic) {
		abort(501, 'Not Implemented');
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\NewsletterTopic  $newsletterTopic
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, NewsletterTopic $newsletterTopic) {
		abort(501, 'Not Implemented');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\NewsletterTopic  $newsletterTopic
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(NewsletterTopic $newsletterTopic) {
		abort(501, 'Not Implemented');
	}
}
