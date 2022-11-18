<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Transformers\NewsletterTemplateTransformer;

class NewslettersTemplatesController extends Controller {
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index() {
		$data = [
			(object) [
				'id'    => 1,
				'title' => 'Prázdne',
				'html'  => view('newsletters.templates.blank')->render(),
				'icon'  => 'mdi-file-outline'
			],
			(object) [
				'id'    => 2,
				'title' => 'Text',
				'html'  => view('newsletters.templates.text')->render(),
				'icon'  => 'mdi-card-text-outline'
			],
			(object) [
				'id'    => 3,
				'title' => 'Tabuľka',
				'html'  => view('newsletters.templates.table')->render(),
				'icon'  => 'mdi-file-table'
			],
		];

		return fractal($data)
			->transformWith(new NewsletterTemplateTransformer)
			->withResourceName('data')
			->respond();
	}
}
