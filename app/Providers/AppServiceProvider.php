<?php

namespace App\Providers;

use App\Services\AI\CodeAnalysisService;
use App\Services\AI\AiderService;
use App\Services\AI\OpenAIService;
use App\Services\AI\AiderServiceInterface;
use App\Services\Parsing\ParserService;
use App\Services\ParsedItemService;
use App\Console\Commands\AiderUpgradeCommand;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ParserService::class, function (Application $app): ParserService {
            return new ParserService($app->make(ParsedItemService::class));
        });

        $this->app->singleton(OpenAIService::class, function (Application $app): OpenAIService {
            return new OpenAIService();
        });

        $this->app->singleton(CodeAnalysisService::class, function (Application $app): CodeAnalysisService {
            return new CodeAnalysisService(
                $app->make(OpenAIService::class),
                $app->make(ParserService::class)
            );
        });

        $this->app->singleton(\App\Services\AnalysisPassService::class, function ($app) {
            return new \App\Services\AnalysisPassService(
                $app->make(\App\Services\AI\OpenAIService::class),
                $app->make(\App\Services\AI\CodeAnalysisService::class),
                $app->make(\App\Services\AI\AiderServiceInterface::class)
            );
        });

        // Register AiderUpgradeCommand
        $this->app->singleton(AiderUpgradeCommand::class, function ($app) {
            return new AiderUpgradeCommand();
        });

        $this->app->singleton(AiderService::class, function ($app) {
            return new AiderService();
        });

        // Bind the interface to the implementation
        $this->app->singleton(AiderServiceInterface::class, function ($app) {
            return new AiderService();
        });

        // Bind ParsedItemService
        $this->app->singleton(ParsedItemService::class, function ($app) {
            return new ParsedItemService();
        });
    }

    public function boot(): void
    {
        // Existing boot logic...

        // Register the command with Artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                AiderUpgradeCommand::class,
            ]);
        }
    }
}
