<?php

namespace App\Providers;

use App\Interfaces\Admin\AiConfigurationServiceInterface;
use App\Interfaces\Admin\AiModelServiceInterface;
use App\Interfaces\Admin\AIPassServiceInterface;
use App\Interfaces\Admin\PassOrderServiceInterface;
use App\Interfaces\Admin\StaticAnalysisToolServiceInterface;
use App\Services\Admin\AiConfigurationService;
use App\Services\Admin\AiModelService;
use App\Services\Admin\AIPassService;
use App\Services\Admin\PassOrderService;
use App\Services\Admin\StaticAnalysisToolService;
use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use App\Services\AnalysisPassService;
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

        $this->app->singleton(FileProcessorService::class, fn ($app) => new FileProcessorService($app->make(ParserService::class)));

        $this->app->singleton(JsonExportService::class, fn ($app) => new JsonExportService);

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

        // Bind AnalysisPassService with injected configuration
        $this->app->singleton(AnalysisPassService::class, function (Application $app) {
            $openAIService = $app->make(OpenAIService::class);
            $codeAnalysisService = $app->make(CodeAnalysisService::class);
            $staticAnalysisService = $app->make(StaticAnalysisToolInterface::class);
            $multiPassConfig = config('ai.ai.passes.pass_order', []);
            $passesConfig = config('ai.passes', []);

            return new AnalysisPassService(
                $openAIService,
                $codeAnalysisService,
                $staticAnalysisService,
                $multiPassConfig,
                $passesConfig
            );
        });

        // Bind AiConfigurationServiceInterface to AiConfigurationService
        $this->app->singleton(AiConfigurationServiceInterface::class, AiConfigurationService::class);

        // Bind AIPassServiceInterface to AIPassService
        $this->app->singleton(AIPassServiceInterface::class, AIPassService::class);

        // Bind PassOrderServiceInterface to PassOrderService
        $this->app->singleton(PassOrderServiceInterface::class, PassOrderService::class);

        // Bind StaticAnalysisToolServiceInterface to StaticAnalysisToolService
        $this->app->singleton(StaticAnalysisToolServiceInterface::class, StaticAnalysisToolService::class);

        // Bind StaticAnalysisToolInterface to StaticAnalysisService
        $this->app->singleton(StaticAnalysisToolInterface::class, fn ($app) => $app->make(StaticAnalysisService::class));

        $this->app->singleton(AiModelServiceInterface::class, fn ($app) => $app->make(AiModelService::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ... boot logic...
    }
}
