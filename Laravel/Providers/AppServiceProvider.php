<?php

namespace App\Providers;

use App\Http\Resources\Banner as BannersResource;
use App\Services;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register() {
		//
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot() {
		Collection::macro('trim', function () {
			return $this->map(function ($value) {
				return trim($value);
			});
		});

		$this->app->singleton(Services\Newsletters::class, function ($app) {
			return new Services\Newsletters;
		});
	}
}
