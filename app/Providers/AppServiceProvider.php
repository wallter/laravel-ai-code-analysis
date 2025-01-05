<?php

namespace App\Providers;

use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use App\Services\Parsing\ParserService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ParserService::class, fn(Application $app): ParserService => new ParserService);

        $this->app->singleton(OpenAIService::class, fn(Application $app): OpenAIService => new OpenAIService);

        $this->app->singleton(CodeAnalysisService::class, fn(Application $app): CodeAnalysisService => new CodeAnalysisService(
            $app->make(OpenAIService::class),
            $app->make(ParserService::class)
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
