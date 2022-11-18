<?php

namespace App;

use App\Events\NewsletterEvent;
use App\Jobs\NewsletterBuild;
use App\Traits\HasByAttributes;
use App\Traits\HasIncludes;
use App\Traits\HasUuid;
use App\Traits\Transforms;
use App\Transformers\NewsletterTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * States:
 * Draft:         send_at IS NULL,  sent_at IS NULL,
 * Ready to send: send_at NOT NULL, sent_at IS NULL,
 * Sent:          send_at NOT NULL, sent_at NOT NULL,
 */
class Newsletter extends Model implements HasMedia {
	use SoftDeletes, HasUuid, HasIncludes, HasByAttributes, Transforms, InteractsWithMedia;

	const STATE_DRAFT            = 'draft';
	const STATE_READY            = 'ready_to_send';
	const STATE_SENDING          = 'sending';
	const STATE_SENDING_STOPPED  = 'sending_stopped';
	const STATE_SENT             = 'sent';

	//TODO: Computed state too fat, refactor
	const STATES = [
		self::STATE_DRAFT,
		self::STATE_READY,
		self::STATE_SENDING,
		self::STATE_SENDING_STOPPED,
		self::STATE_SENT,
	];

	const BUILD_STATE_NOT_BUILT = 'not_built';
	const BUILD_STATE_BUILDING  = 'building';
	const BUILD_STATE_BUILT     = 'built';

	const SEG_AGGR_UNION = 'union';
	const SEG_AGGR_INTERSECT = 'intersect';

	const SEG_AGGR = [
		self::SEG_AGGR_UNION,
		self::SEG_AGGR_INTERSECT,
	];

	const UNSUB_REASONS = [
		'spamming'                => 'Posielate mi e-maily príliš často',
		'content_not_interesting' => 'Zaslaný obsah nie je pre mňa zaujímavý',
		'never_signed_up'         => 'Nikdy som sa neprihlásil do tohto mailing listu',
		'other'                   => 'Iný dôvod',
	];

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'build_state',
		'topic_id',
		'title',
		'subject',
		'segments_aggregate',
		'send_at',
		'send_start_at',
		'send_stop_at',
		'sent_at',
		'recipient_count',
	];

	protected $transformer = NewsletterTransformer::class;

	// Relations
	public function topic() {
		return $this->belongsTo(NewsletterTopic::class);
	}

	public function content() {
		return $this->hasOne(NewsletterContent::class)->orderBy('created_at', 'desc');
	}

	public function contentHistory() {
		return $this->hasMany(NewsletterContent::class)->orderBy('created_at', 'desc')->limit(10);
	}

	public function customers() {
		return $this->belongsToMany(Customer::class, 'newsletters_customers', 'newsletter_id', 'customer_id');
	}

	public function segments() {
		return $this->belongsToMany(Segment::class, 'newsletters_segments', 'newsletter_id', 'segment_id');
	}

	public function pricelists() {
		return $this->belongsToMany(PricelistListModel::class, 'newsletters_pricelists', 'newsletter_id', 'pricelist_id');
	}

	// Uncomment only, if really needed. (Large data sets)
	// public function recipients() {
	// 	return $this->hasMany(NewsletterRecipient::class, 'newsletter_id');
	// }

	// Setup
	public function registerMediaCollections(): void {
		$this->addMediaCollection('attachments')
			->useDisk('newsletters_attachments')
			->acceptsMimeTypes(['application/pdf']);
	}

	// By Attributes
	public function createdBy() {
		return $this->belongsTo(User::class, 'created_by');
	}

	public function updatedBy() {
		return $this->belongsTo(User::class, 'updated_by');
	}

	public function deletedBy() {
		return $this->belongsTo(User::class, 'deleted_by');
	}

	// Getters & Setters
	//TODO: Computed state too fat, refactor
	public function getStateAttribute() {
		switch (true) {
			case !is_null($this->attributes['sent_at']):
				return static::STATE_SENT;
			case is_null($this->attributes['sent_at']) && !is_null($this->attributes['send_start_at']) && is_null($this->attributes['send_stop_at']):
				return static::STATE_SENDING;
			case is_null($this->attributes['sent_at']) && !is_null($this->attributes['send_start_at']) && !is_null($this->attributes['send_stop_at']):
				return static::STATE_SENDING_STOPPED;
			case !is_null($this->attributes['send_at']) || $this->build_state == static::BUILD_STATE_BUILT:
				return static::STATE_READY;
			default:
				return static::STATE_DRAFT;
		}
	}

	public function setStateAttribute() {
		throw new RuntimeException('State attribute is readonly');
	}

	public function getBuildStateAttribute() {
		switch (true) {
			case is_null($this->attributes['recipient_count']):
				return self::BUILD_STATE_NOT_BUILT;
			case $this->attributes['recipient_count'] === -1:
				return self::BUILD_STATE_BUILDING;
			case $this->attributes['recipient_count'] > -1:
				return self::BUILD_STATE_BUILT;
		}
	}

	public function setBuildStateAttribute() {
		throw new RuntimeException('Build state attribute is readonly');
	}

	public function getRecipientCountAttribute() {
		if ($this->attributes['recipient_count'] === -1) {
			return null;
		}
		return $this->attributes['recipient_count'];
	}

	public function getAttachmentsAttribute() {
		return $this->getMedia('attachments');
	}

	// Scopes
	public function scopeFilter(Builder &$qb, Request $request) {
		if ($request->filled('state')) {
			$qb->inState($request->state);
		}

		if ($request->filled('topic')) {
			$qb->whereIn('topic_id', item_or_items($request->topic));
		}

		if ($request->filled('segment')) {
			$qb->whereHas('segments', function (Builder $qb) use (&$request) {
				$qb->whereIn('segments_list.id', item_or_items($request->segment));
			});
		}

		if ($request->filled('sentAtFrom')) {
			$qb->where('sent_at', '>=', $request->sentAtFrom.' 00:00:00');
		}

		if ($request->filled('sentAtTo')) {
			$qb->where('sent_at', '<=', $request->sentAtTo.' 23:59:59');
		}
	}

	public function scopeInState(Builder &$qb, string $state) {
		switch (true) {
			case static::STATE_DRAFT == $state:
				$qb->whereNull('send_at')->whereNull('sent_at');
				break;
			case static::STATE_READY == $state:
				$qb->whereNotNull('send_at')->whereNull('sent_at');
				break;
			case static::STATE_SENT == $state:
				$qb->whereNotNull('send_at')->whereNotNull('sent_at');
				break;
		}
	}

	/**
	 * Apply sorter
	 * sort=created_at:desc,updated_at:asc
	 * sort[]=created_at:desc&sort[]=updated_at:asc
	 * @param Builder $qb
	 * @param Request $request
	 * @param string $param
	 * @return void
	 */
	public function scopeSort(Builder &$qb, Request $request, string $param = 'sort') {
		if ($request->has($param)) {
			$sort = collect(Arr::wrap($request->{$param}))->map(function ($item) {
				return explode(',', $item);
			})->flatten()->map(function ($item) {
				return explode(':', $item);
			})->filter(function ($item) {
				return Schema::hasColumn($this->getTable(), $item[0]);
			})->all();
			foreach ($sort as $sorter) {
				$qb->orderBy($sorter[0], $sorter[1]);
			}
		}
	}

	// Methods
	public function hasState(...$states) {
		return collect($states)->flatten()->contains($this->state);
	}

	/**
	 * Update relationships via request
	 *
	 * @param Request $request
	 * @param boolean $build
	 * @return void
	 */
	public function updateRelationships(Request $request, bool $build = false) {
		if ($request->filled('customers')) {
			$this->customers()->sync(sync_collection($request->customers));
		}
		
		if ($request->filled('segments')) {
			// Sync only enabled segments
			$segments = Segment::select('id')
				->whereIn('id', sync_collection($request->segments))
				->onlyEnabled()
				->get()->pluck('id');
			$this->segments()->sync($segments);
		}
		
		if ($request->filled('pricelists')) {
			// Sync only enabled pricelists
			$pricelists = PricelistListModel::select('id')
				->whereIn('id', sync_collection($request->pricelists))
				->onlyEnabled()
				->get()->pluck('id');
			$this->pricelists()->sync($pricelists);
		}

		if ($request->filled('content')) {
			$this->content()->save(new NewsletterContent(['html' => $request->content]));
		}

		// TODO: maybe not right place for this
		// Dispatch build job after update if newsletter have been build previously
		if ($build && $request->hasAny(['customers', 'segments', 'pricelists']) && $this->recipient_count >= -1) {
			$this->update(['recipient_count' => -1]); // Sets state to "building"
			broadcast(new NewsletterEvent($this, 'update', 'build.start'));

			dispatch((new NewsletterBuild($this, true))
				->force()
				->withBroadcasts('finish', 'error'))
				->onConnection(config('queue.default'))
				->onQueue(config('newsletters.queue'));
		}

		return $this;
	}

	/**
	 * Get view data
	 *
	 * @param NewsletterRecipient|null $recipient
	 * @return array
	 */
	public function getViewData(NewsletterRecipient $recipient = null) {
		// View::share('newsletter_uuid', $this->uuid);
		// View::share('recipient_uuid', $recipient ? $recipient->uuid : null);

		$this->load('content');
		$content = $this->content ? $this->content->html : null;

		$content = preg_replace_callback('/href=\"(.*?)\"/', function ($matches) use (&$recipient) {
			return 'href="'.route('newsletters.click', [
				'newsletter_uuid' => $this->uuid,
				'recipient'       => $recipient ? $recipient->uuid : null,
				'href'            => $matches[1],
			]).'"';
		}, $content);

		return [
			'newsletter_uuid' => $this->uuid,
			'recipient_uuid'  => $recipient ? $recipient->uuid : null,
			'content'         => $content,
		];
	}

	/**
	 * View
	 *
	 * @param NewsletterRecipient|null $recipient
	 * @param string $template
	 * @return void
	 */
	public function view(NewsletterRecipient $recipient = null, string $template = 'template') {
		return view('newsletters.'.$template, $this->getViewData($recipient));
	}
}
