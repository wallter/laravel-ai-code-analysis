<?php

namespace App\Providers;

use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use App\Services\Parsing\ParserService;
use App\Models\CodeAnalysis;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ParserService::class, function ($app) {
            return new ParserService(new CodeAnalysis());
        });

        $this->app->singleton(CodeAnalysisService::class, function ($app) {
            return new CodeAnalysisService($app->make(OpenAIService::class));
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
