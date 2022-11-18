<?php

namespace App\Console\Commands;

use App\Customer;
use App\Jobs\NewsletterBuild as NewsletterBuildJob;
use App\Jobs\NewsletterSend as NewsletterSendJob;
use App\Mail\NewsletterMail;
use App\Newsletter;
use App\NewsletterRecipient;
use App\User;
use Artisan;
use DB;
use Exception;
use Illuminate\Console\Command;
use Mail;
use Ramsey\Uuid\Uuid;

class NewsletterSend extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'newsletter:send {id} {--build} {--fresh} {--toall}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Newsletter send';

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

		newsletters()->log()->info('Executing newsletter:send', ['id' => $id]);

		$newsletter = Newsletter::whereId($id)->orWhere('uuid', $id)->first();

		if (!$newsletter) {
			return $this->error('Newsletter with id or uuid `'.$id.'` not found');
		}

		if ($newsletter->state == Newsletter::STATE_SENT) {
			$confirm = $this->confirm('Newsletter is sent. Really send ?');

			if (!$confirm) {
				return $this->info('Cancelled');
			}

			newsletters()->log()->info('Re-sending');
		}

		try {
			if ($this->option('build')) {
				dispatch(new NewsletterBuildJob($newsletter, $this->option('fresh')))->onConnection('sync');
			}

			$sendJob = new NewsletterSendJob($newsletter);
			$sendJob->force();

			if ($this->option('toall')) {
				$sendJob->toAll();
			}
	
			dispatch($sendJob)->onConnection('sync');
		} catch (Exception $e) {
			throw $e;
			$this->error($e->getMessage());
		}
	}
}
