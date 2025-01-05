<?php

namespace App\Providers;

use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use App\Services\Parsing\ParserService;
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
        $this->app->singleton(ParserService::class, fn(Application $app): ParserService => new ParserService);

        $this->app->singleton(OpenAIService::class, fn(Application $app): OpenAIService => new OpenAIService);

        $this->app->singleton(CodeAnalysisService::class, fn(Application $app): CodeAnalysisService => new CodeAnalysisService(
            $app->make(OpenAIService::class),
            $app->make(ParserService::class)
        ));
        // Register AiderUpgradeCommand
        $this->app->singleton(AiderUpgradeCommand::class, function ($app) {
            return new AiderUpgradeCommand();
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
