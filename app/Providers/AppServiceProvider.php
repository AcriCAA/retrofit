<?php

namespace App\Providers;

use App\Services\Marketplaces\MarketplaceManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MarketplaceManager::class);
    }

    public function boot(): void
    {
        //
    }
}
