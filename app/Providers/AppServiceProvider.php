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

    $this->app->singleton(CodeAnalysisService::class, function ($app) {
        return new CodeAnalysisService($app->make(OpenAIService::class));
    });

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
