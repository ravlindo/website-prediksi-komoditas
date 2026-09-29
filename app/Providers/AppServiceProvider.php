<?php

namespace App\Providers;

use App\Models\CommodityPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['partials.sidebar', 'partials.topbar'], function ($view) {
            $latestDataDate = CommodityPrice::max('price_date');
            $view->with([
                'latestDataDate' => $latestDataDate ? Carbon::parse($latestDataDate) : null,
                'currentDate' => now(),
            ]);
        });
    }
}
