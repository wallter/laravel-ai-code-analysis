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
        // Register ParserService as a singleton
        $this->app->singleton(ParserService::class, ParserService::class);

        // Register OpenAIService as a singleton
        $this->app->singleton(OpenAIService::class, function ($app) {
            return new OpenAIService();
        });

        // Register CodeAnalysisService with its dependencies
        $this->app->singleton(CodeAnalysisService::class, function ($app) {
            return new CodeAnalysisService(
                $app->make(OpenAIService::class),
                $app->make(ParserService::class)
            );
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
