<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Media;
use App\Newsletter;
use App\Transformers\MediaTransformer;
use Illuminate\Http\Request;

class NewslettersAttachments extends Controller {
	/**
	 * Display a listing of the resource.
	 *
	 * @param  \App\Newsletter  $newsletter
	 * @return \Illuminate\Http\Response
	 */
	public function index(Newsletter $newsletter) {
		abort(501, 'Not Implemented');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Newsletter  $newsletter
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request, Newsletter $newsletter) {
		$this->validate($request, [
			'file' => ['required', 'mimes:pdf', 'max:10240'],
		]);

		$newsletter->addMedia($request->file('file'))->toMediaCollection('attachments');

		return fractal($newsletter->getMedia('attachments'))
			->transformWith(new MediaTransformer)
			->withResourceName('data')
			->respond();
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Media  $media
	 * @return \Illuminate\Http\Response
	 */
	public function show(Request $request, Media $media) {
		abort(501, 'Not Implemented');
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Media  $media
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Media $media) {
		abort(501, 'Not Implemented');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Media  $media
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Media $media) {
		$media->delete();
		return fractal($media->model->getMedia('attachments'))
			->transformWith(new MediaTransformer)
			->withResourceName('data')
			->respond();
	}
}
