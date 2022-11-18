<?php

namespace App\Console\Commands;

use App\Jobs\NewsletterBuild as NewsletterBuildJob;
use App\Newsletter;
use Exception;
use Illuminate\Console\Command;

class NewsletterBuild extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'newsletter:build {id} {--fresh}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Build newsletter';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle() {
		$id = $this->argument('id');

		newsletters()->log()->info('Executing newsletter:build', ['id' => $id]);

		$newsletter = Newsletter::whereId($id)->orWhere('uuid', $id)->first();

		if (!$newsletter) {
			return $this->error('Newsletter with id or uuid `'.$id.'` not found');
		}

		if ($newsletter->state == Newsletter::STATE_SENT) {
			$confirm = $this->confirm('Newsletter is sent. Really build ?');

			if (!$confirm) {
				return $this->info('Cancelled');
			}
		}

		try {
			dispatch(new NewsletterBuildJob($newsletter, $this->option('fresh')))->onConnection('sync');
		} catch (Exception $e) {
			$this->error($e->getMessage());
		}
	}
}
