<?php

namespace App\Providers;

use App\Services\DataForSeoService;
use Illuminate\Support\ServiceProvider;

class DataForSeoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(DataForSeoService::class, function ($app) {
            return new DataForSeoService();
        });
    }
}
