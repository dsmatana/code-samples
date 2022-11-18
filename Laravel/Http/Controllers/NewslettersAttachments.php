<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Media;
use Illuminate\Http\Request;

class NewslettersAttachments extends Controller {

	/**
	 * Display the specified resource.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Media  $media
	 * @return \Illuminate\Http\Response
	 */
	public function show(Request $request, Media $media) {
		return $media->toInlineResponse($request);
	}
}
