<?php

namespace App\Providers;

use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use App\Interfaces\Admin\AiModelServiceInterface;
use App\Services\Admin\AiModelService;
use App\Interfaces\Admin\AiConfigurationServiceInterface;
use App\Services\Admin\AiConfigurationService;
use App\Interfaces\Admin\AIPassServiceInterface;
use App\Services\Admin\AIPassService;
use App\Interfaces\Admin\PassOrderServiceInterface;
use App\Services\Admin\PassOrderService;
use App\Interfaces\Admin\StaticAnalysisToolServiceInterface;
use App\Services\Admin\StaticAnalysisToolService;
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
        // Singleton bindings for services
        // Bind ParserService with the injected base path
        $this->app->singleton(ParserService::class, function (Application $app) {
            $parsedItemService = $app->make(ParsedItemService::class);
            $basePath = config('filesystems.base_path');
            return new ParserService($parsedItemService, $basePath);
        });

        $this->app->singleton(FileProcessorService::class, function ($app) {
            return new FileProcessorService($app->make(ParserService::class));
        });

        $this->app->singleton(JsonExportService::class, function ($app) {
            return new JsonExportService;
        });

        // Bind OpenAIService with the injected API key
        $this->app->singleton(OpenAIService::class, function (Application $app) {
            $apiKey = config('ai.openai_api_key');
            return new OpenAIService($apiKey);
        });

        // Bind CodeAnalysisService with the injected base path
        $this->app->singleton(CodeAnalysisService::class, function (Application $app) {
            $openAIService = $app->make(OpenAIService::class);
            $parserService = $app->make(ParserService::class);
            $basePath = base_path(); // or config('filesystems.base_path');
            return new CodeAnalysisService($openAIService, $parserService, $basePath);
        });

        // Bind AiModelServiceInterface to AiModelService
        $this->app->singleton(AiModelServiceInterface::class, AiModelService::class);

        // Bind AiConfigurationServiceInterface to AiConfigurationService
        $this->app->singleton(AiConfigurationServiceInterface::class, AiConfigurationService::class);

        // Bind AIPassServiceInterface to AIPassService
        $this->app->singleton(AIPassServiceInterface::class, AIPassService::class);

        // Bind PassOrderServiceInterface to PassOrderService
        $this->app->singleton(PassOrderServiceInterface::class, PassOrderService::class);

        // Bind StaticAnalysisToolServiceInterface to StaticAnalysisToolService
        $this->app->singleton(StaticAnalysisToolServiceInterface::class, StaticAnalysisToolService::class);

        // Bind StaticAnalysisToolInterface to StaticAnalysisService
        $this->app->singleton(StaticAnalysisToolInterface::class, function ($app) {
            return $app->make(StaticAnalysisService::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ... boot logic...
    }
}
