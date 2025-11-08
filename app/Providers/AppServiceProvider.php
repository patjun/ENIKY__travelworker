<?php

namespace App\Providers;

use App\Models\Listicle;
use App\Observers\ListicleObserver;
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
        Listicle::observe(ListicleObserver::class);
    }
}
