<?php

namespace App\Providers;

use App\Console\Commands\AiderUpgradeCommand;
use App\Console\Commands\Queue\ListQueuedJobs; // Added import for ListQueuedJobs
use App\Services\AI\AiderService;
use App\Services\AI\AiderServiceInterface;
use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use App\Services\Export\JsonExportService;
use App\Services\ParsedItemService;
use App\Services\Parsing\FileProcessorService;
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
        $this->app->singleton(ParserService::class, fn(Application $app): ParserService => new ParserService($app->make(ParsedItemService::class)));

        // Register FileProcessorService
        $this->app->singleton(FileProcessorService::class, fn($app) => new FileProcessorService($app->make(ParserService::class)));

        // Register JsonExportService
        $this->app->singleton(JsonExportService::class, fn($app) => new JsonExportService);

        $this->app->singleton(OpenAIService::class, fn(Application $app): OpenAIService => new OpenAIService);

        $this->app->singleton(CodeAnalysisService::class, fn(Application $app): CodeAnalysisService => new CodeAnalysisService(
            $app->make(OpenAIService::class),
            $app->make(ParserService::class)
        ));

        $this->app->singleton(\App\Services\AnalysisPassService::class, fn($app) => new \App\Services\AnalysisPassService(
            $app->make(\App\Services\AI\OpenAIService::class),
            $app->make(\App\Services\AI\CodeAnalysisService::class),
            $app->make(\App\Services\AI\AiderServiceInterface::class)
        ));

        // Register AiderUpgradeCommand
        $this->app->singleton(AiderUpgradeCommand::class, fn($app) => new AiderUpgradeCommand);

        $this->app->singleton(AiderService::class, fn($app) => new AiderService);

        // Bind the interface to the implementation
        $this->app->singleton(AiderServiceInterface::class, fn($app) => new AiderService);

        // Bind ParsedItemService
        $this->app->singleton(ParsedItemService::class, fn($app) => new ParsedItemService);
    }

    public function boot(): void
    {
        // ... boot logic...

        // Register the commands with Artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                AiderUpgradeCommand::class,
                ListQueuedJobs::class, // Added registration for ListQueuedJobs
            ]);
        }
    }
}
