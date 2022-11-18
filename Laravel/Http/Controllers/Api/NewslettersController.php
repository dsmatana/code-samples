<?php

namespace App\Http\Controllers\Api;

use App\Events\NewsletterEvent;
use App\Exports\NewsletterRecipientsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\NewsletterCreate;
use App\Http\Requests\NewsletterUpdate;
use App\Jobs\IcosDownload;
use App\Jobs\NewsletterBuild;
use App\Jobs\NewsletterSend;
use App\Jobs\Sleep;
use App\Mail\NewsletterMail;
use App\Newsletter;
use App\Rules\NumericOrArray;
use App\Transformers\NewsletterTransformer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Mail;

class NewslettersController extends Controller {
	public function __construct() {
		$this->authorizeResource(Newsletter::class);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(Request $request) {
		$this->validate($request, [
			'state'      => [Rule::in(Newsletter::STATES) ],
			'topic'      => [new NumericOrArray],
			'topic.*'    => ['numeric'],
			'segment'    => [new NumericOrArray],
			'segment.*'  => ['numeric'],
			'sentAtFrom' => ['date'],
			'sentAtTo'   => ['date'],
			'page'       => ['numeric'],
			'limit'      => ['numeric', 'min:1', 'max:30'],
			'sort'       => ['array'],
			'sort.*'     => ['regex:/^[A-Za-z_]+:(asc|desc)$/'],
		], [
			'sort.*.regex' => 'The :attribute format must be *prop*:asc|desc',
		]);

		$qb = Newsletter::filter($request)
			->withIncludes($request)
			->sort($request);

		$result = $qb->paginate($request->input('limit', 20));

		return fractal($result)
			->transformWith(new NewsletterTransformer)
			->withResourceName('data')
			->respond();
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \App\Http\Requests\NewsletterCreate  $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(NewsletterCreate $request) {
		$newsletter = Newsletter::create($request->only(['title', 'subject', 'segments_aggregate', 'topic_id', 'send_at']));
		$newsletter->updateRelationships($request);
		$newsletter = $newsletter->fresh();
		broadcast(new NewsletterEvent($newsletter, 'create'));
		return $newsletter->transform('*')->respond();
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Newsletter  $newsletter
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(Newsletter $newsletter) {
		return $newsletter->transform('*')->respond();
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \App\Http\Requests\NewsletterUpdate  $request
	 * @param  \App\Newsletter  $newsletter
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update(NewsletterUpdate $request, Newsletter $newsletter) {
		$newsletter->update($request->only(['title', 'subject', 'segments_aggregate', 'topic_id', 'send_at']));
		$newsletter->updateRelationships($request, true);
		$newsletter = $newsletter->fresh();
		broadcast(new NewsletterEvent($newsletter, 'update'));
		return  $newsletter->transform('*')->respond();
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Newsletter  $newsletter
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Newsletter $newsletter) {
		// TODO: authorize
		$newsletter->delete();
		broadcast(new NewsletterEvent($newsletter, 'delete'));
		return response('', 204);
	}

	/**
	 * Build newsletter
	 *
	 * @param Request $request
	 * @param Newsletter $newsletter
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function build(Request $request, Newsletter $newsletter) {
		$this->validate($request, [
			'fresh' => ['boolean'],
			'sync'  => ['boolean'],
		]);
		$this->authorize('build', $newsletter);

		$newsletter->update(['recipient_count' => -1]); // Sets state to "building"
		broadcast(new NewsletterEvent($newsletter, 'update', 'build.start'));

		dispatch((new NewsletterBuild($newsletter, $request->input('fresh', true)))
			->force()
			->withBroadcasts('finish', 'error'))
			->onConnection($request->sync ? 'sync' : config('queue.default'))
			->onQueue(config('newsletters.queue'));
			
		return response('', 204);
	}

	/**
	 * Send newsletter
	 *
	 * @param Request $request
	 * @param Newsletter $newsletter
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function send(Request $request, Newsletter $newsletter) {
		$this->authorize('send', $newsletter);

		$newsletter->update(['send_start_at' => now(), 'recipient_count' => -1]); // Fill date and set state to "building"
		broadcast((new NewsletterEvent($newsletter, 'update', 'send.start')));

		// IcosDownload::withChain([
		// 	(new NewsletterBuild($newsletter, true))->force()->withBroadcasts('finish'),
		// 	(new NewsletterSend($newsletter))->withBroadcasts('progress', 'finish'),
		// ])->dispatch()->allOnQueue(config('newsletters.queue'));
		
		// Debug
		Sleep::withChain([
			(new NewsletterBuild($newsletter, true))->withBroadcasts('finish'),
			(new NewsletterSend($newsletter))->withBroadcasts('progress', 'finish'),
		])->dispatch()->allOnQueue(config('newsletters.queue'));

		return response('', 204);
	}

	/**
	 * Send newsletter to selected recipients
	 *
	 * @param Request $request
	 * @param Newsletter $newsletter
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendTest(Request $request, Newsletter $newsletter) {
		$this->validate($request, [
			'recipients'   => ['required', 'array'],
			'recipients.*' => ['email'],
		]);

		newsletters()->log()->info('Sending test to custom recipients', ['newsletter' => $newsletter->id, 'emails' => $request->recipients]);
		Mail::queue(new NewsletterMail($newsletter, null, $request->recipients));

		return response('', 204);
	}

	/**
	 * Stop sending newsletter
	 *
	 * @param Request $request
	 * @param Newsletter $newsletter
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendStop(Request $request, Newsletter $newsletter) {
		$this->authorize('sendStop', $newsletter);
		$newsletter->update(['send_stop_at' => now()]);
		broadcast((new NewsletterEvent($newsletter, 'update', 'send.stop')));
		return response('', 204);
	}

	/**
	 * Stop sending newsletter
	 *
	 * @param Request $request
	 * @param Newsletter $newsletter
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendResume(Request $request, Newsletter $newsletter) {
		$this->authorize('sendResume', $newsletter);
		
		$newsletter->update(['send_stop_at' => null]);
		broadcast((new NewsletterEvent($newsletter, 'update', 'send.resume')));

		dispatch((new NewsletterSend($newsletter))->withBroadcasts('progress', 'finish'));

		return response('', 204);
	}

	/**
	 * Export recipients
	 *
	 * @param Newsletter $newsletter
	 * @return void
	 */
	public function exportRecipients(Newsletter $newsletter) {
		// TODO: protect this route, then uncomment
		// $this->authorize('exportRecipients', $newsletter);
		$filename = sprintf('newsletter-adresati-%s.xlsx', Str::kebab($newsletter->title));
		return (new NewsletterRecipientsExport($newsletter))->download($filename);
	}
}
