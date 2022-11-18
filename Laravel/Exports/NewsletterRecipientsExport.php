<?php

namespace App\Exports;

use App\Customer;
use App\DeliveryAddress;
use App\Newsletter;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class NewsletterRecipientsExport implements FromArray, WithEvents, WithColumnWidths {
	use Exportable;

	/**
	 * Audience title map
	 *
	 * @var array
	 */
	protected $audienceTitleMap = [
		Customer::class => 'Zákazník',
		DeliveryAddress::class   => 'Dodacia adresa',
		User::class              => 'Používateľ',
	];

	/**
	 * Newsletter
	 *
	 * @var Newsletter
	 */
	protected $newsletter;

	public function __construct(Newsletter $newsletter) {
		$this->newsletter = $newsletter;
	}

	public function registerEvents(): array {
		return [
		];
	}

	public function columnWidths(): array {
		return [
			'A' => 25,
			'B' => 20,
			'C' => 25,
			'D' => 25,
			'E' => 25,
			'F' => 15,
		];
	}

	public function array(): array {
		// TODO: optimize for large data sets
		$records = DB::select('SELECT * FROM newsletters_recipients WHERE newsletter_id = '.$this->newsletter->id.' ORDER BY recipient_model_type, email');

		$records = collect($records)->map(function ($item) {
			return [
				$item->email,
				$this->audienceTitleMap[$item->recipient_model_type],
				$item->sent_at       ? Carbon::parse($item->sent_at)->format('j.n.Y H:i:s') : '',
				$item->impressed_at  ? Carbon::parse($item->impressed_at)->format('j.n.Y H:i:s') : '',
				$item->last_click_at ? Carbon::parse($item->last_click_at)->format('j.n.Y H:i:s') : '',
				$item->clicks_count,
			];
		});

		return collect([$this->header()])->merge($records)->all();
	}

	protected function header(): array {
		return [
			'E-Mail',
			'Typ adresáta',
			'Čas odoslania',
			'Čas prvého prezretia',
			'Čas posledného kliku',
			'Počet klikov',
		];
	}
}
