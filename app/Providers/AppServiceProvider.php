<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Parsing\ParserService;
use App\Services\AI\DocEnhancer;

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
        
        $this->app->singleton(DocEnhancer::class, function ($app) {
            return new DocEnhancer();
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
