<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Parsing\ParserService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ParserService::class, function ($app) {
            return new ParserService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
