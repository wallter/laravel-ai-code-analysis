<?php

namespace App\Providers;

use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use App\Interfaces\Admin\AiModelServiceInterface;
use App\Services\Admin\AiModelService;
use App\Services\Export\JsonExportService;
use App\Services\ParsedItemService;
use App\Services\Parsing\FileProcessorService;
use App\Services\Parsing\ParserService;
use App\Services\StaticAnalysis\StaticAnalysisToolInterface;
use App\Services\StaticAnalysisService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ParserService::class, fn (Application $app): ParserService => new ParserService($app->make(ParsedItemService::class)));

        // Register FileProcessorService
        $this->app->singleton(FileProcessorService::class, fn ($app) => new FileProcessorService($app->make(ParserService::class)));

        // Register JsonExportService
        $this->app->singleton(JsonExportService::class, fn ($app) => new JsonExportService);

        // Register OpenAIService
        $this->app->singleton(OpenAIService::class, fn (Application $app): OpenAIService => new OpenAIService);

        // Register CodeAnalysisService
        $this->app->singleton(CodeAnalysisService::class, fn (Application $app): CodeAnalysisService => new CodeAnalysisService(
            $app->make(OpenAIService::class),
            $app->make(ParserService::class)
        ));

        // Bind AiModelServiceInterface to AiModelService
        $this->app->singleton(AiModelServiceInterface::class, AiModelService::class);

        // Register AnalysisPassService with updated constructor parameters
        $this->app->singleton(\App\Services\AnalysisPassService::class, fn ($app) => new \App\Services\AnalysisPassService(
            $app->make(\App\Services\AI\OpenAIService::class),
            $app->make(\App\Services\AI\CodeAnalysisService::class),
            $app->make(StaticAnalysisToolInterface::class)
        ));

        // Bind the StaticAnalysisToolInterface to the StaticAnalysisService implementation
        $this->app->singleton(StaticAnalysisService::class, fn ($app) => new StaticAnalysisService);
        $this->app->singleton(StaticAnalysisToolInterface::class, fn ($app) => $app->make(StaticAnalysisService::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ... boot logic...
    }
}
