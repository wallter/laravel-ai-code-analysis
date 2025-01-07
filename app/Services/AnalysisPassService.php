<?php

namespace App\Services;

use App\Enums\OperationIdentifier;
use App\Models\AIResult;
use App\Models\CodeAnalysis;
use App\Services\AI\AIPromptBuilder;
use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for handling AI analysis passes for code.
 */
class AnalysisPassService
{
    /**
     * Constructor to inject dependencies.
     */
    public function __construct(
        protected OpenAIService $openAIService,
        protected CodeAnalysisService $codeAnalysisService
    ) {}

    /**
     * Process all pending AI analysis passes for a given CodeAnalysis.
     */
    public function processAllPasses(int $codeAnalysisId, bool $dryRun = false): void
    {
        Log::info("AnalysisPassService: Starting processAllPasses for CodeAnalysis ID {$codeAnalysisId}, dryRun: ".($dryRun ? 'true' : 'false'));

        try {
            // Start a database transaction to ensure atomicity
            DB::transaction(function () use ($codeAnalysisId, $dryRun) {
                $analysis = $this->retrieveAnalysis($codeAnalysisId);
                if (! $analysis) {
                    Log::warning("AnalysisPassService: No analysis found for CodeAnalysis ID {$codeAnalysisId}. Exiting processAllPasses.");

                    return;
                }

                $passOrder = config('ai.operations.multi_pass_analysis.pass_order', []);

                // Initialize pass-specific processing
                foreach ($passOrder as $passName) {
                    // Dispatch individual pass jobs
                    dispatch(new \App\Jobs\ProcessIndividualPassJob($codeAnalysisId, $passName, $dryRun));
                }

                foreach ($passOrder as $passName) {
                    if (in_array($passName, $completedPasses, true)) {
                        Log::info("AnalysisPassService: Pass '{$passName}' already completed for CodeAnalysis ID {$codeAnalysisId}. Skipping.");

                        continue;
                    }

                    $passConfig = $this->getPassConfig($passName);
                    if (! $passConfig) {
                        Log::warning("AnalysisPassService: No configuration found for pass '{$passName}'. Skipping.");

                        continue;
                    }

                    if ($dryRun) {
                        Log::info("[DRY-RUN] => would run pass [{$passName}] for [{$analysis->file_path}].");

                        continue;
                    }

                    Log::info("AnalysisPassService: Executing pass '{$passName}' for CodeAnalysis ID {$codeAnalysisId}.");
                    $aiResult = $this->executeAiOperation($analysis, $passName, $passConfig);
                    Log::info("AnalysisPassService: AI operation completed for pass '{$passName}'.");

                    Log::info("AnalysisPassService: Extracting usage metrics for pass '{$passName}'.");
                    $metadata = $this->extractUsageMetrics();
                    Log::debug('AnalysisPassService: Usage metrics extracted: '.json_encode($metadata));

                    Log::info("AnalysisPassService: Creating AIResult entry for pass '{$passName}'.");
                    $this->createAiResultEntry(
                        $analysis,
                        $passName,
                        $aiResult['prompt'],
                        $aiResult['response_data'],
                        $metadata
                    );

                    // Mark pass as completed
                    $completedPasses[] = $passName;
                    $analysis->completed_passes = array_unique($completedPasses);
                    $analysis->save();

                    Log::info("AnalysisPassService: Pass '{$passName}' marked as completed for CodeAnalysis ID {$codeAnalysisId}.");
                }

                Log::info("AnalysisPassService: All passes processed for CodeAnalysis ID {$codeAnalysisId}.");
            });
        } catch (Exception $exception) {
            Log::error('AnalysisPassService: Exception in processAllPasses => '.$exception->getMessage(), ['exception' => $exception]);
        }

        Log::info("AnalysisPassService: Finished processAllPasses for CodeAnalysis ID {$codeAnalysisId}.");
    }

    protected function updateTotalCost(CodeAnalysis $analysis): void
    {
        $totalCost = $analysis->aiResults()->sum('cost_estimate_usd');
        $analysis->total_cost_usd = $totalCost;
        $analysis->save();
    }

    /**
     * Retrieve the CodeAnalysis instance from the database.
     */
    private function retrieveAnalysis(int $codeAnalysisId): ?CodeAnalysis
    {
        Log::debug("AnalysisPassService: Retrieving CodeAnalysis with ID {$codeAnalysisId}.");
        $analysis = CodeAnalysis::find($codeAnalysisId);
        if (! $analysis) {
            Log::warning("AnalysisPassService: CodeAnalysis [{$codeAnalysisId}] not found. Skipping.");
        }

        return $analysis;
    }

    /**
     * Retrieve the configuration for the specified pass.
     */
    private function getPassConfig(string $passName): ?array
    {
        Log::debug("AnalysisPassService: Retrieving configuration for pass '{$passName}'.");
        $passConfigs = config('ai.passes', []);
        $cfg = $passConfigs[$passName] ?? null;
        if (! $cfg) {
            Log::warning("AnalysisPassService: No config for pass '{$passName}'. Skipping.");
        }

        return $cfg;
    }

    /**
     * Execute the AI operation for a given pass.
     */
    private function executeAiOperation(CodeAnalysis $analysis, string $passName, array $passConfig): array
    {
        // Initialize AiPromptBuilder with ENUM
        try {
            $operationIdentifier = OperationIdentifier::from($passConfig['operation_identifier']);
        } catch (\ValueError $valueError) {
            Log::error("AnalysisPassService: Invalid OperationIdentifier '{$passConfig['operation_identifier']}' for pass '{$passName}'. Skipping.");
            throw $valueError;
        }

        // Generate a unique cache key based on CodeAnalysis ID and pass name
        $cacheKey = "ai_response_{$analysis->id}_{$passName}";

        // Check if response is cached
        if (Cache::has($cacheKey)) {
            Log::info("AnalysisPassService: Retrieved cached response for pass '{$passName}'.");
            $responseData = Cache::get($cacheKey);

            return [
                'prompt' => '', // No need to rebuild prompt if cached
                'response_data' => $responseData,
            ];
        }

        $promptBuilder = new AIPromptBuilder(
            operationIdentifier: $operationIdentifier,
            config: $passConfig,
            astData: $analysis->ast ?? [],
            rawCode: $this->getRawCode($analysis->file_path),
            previousResults: $this->getPreviousResults($analysis)
        );

        // Build prompt
        Log::debug("AnalysisPassService: Building prompt for pass '{$passName}'.");
        $prompt = $promptBuilder->buildPrompt();
        Log::debug("AnalysisPassService: Prompt built for pass '{$passName}': {$prompt}");

        // Determine the correct token parameter based on model configuration
        $modelName = $passConfig['model'] ?? config('ai.default.model');
        $modelConfig = config("ai.models.{$modelName}", []);
        $tokenLimitParam = $modelConfig['token_limit_parameter'] ?? 'max_tokens';

        // Prepare AI operation parameters
        $aiParams = [
            'model' => $modelConfig['model_name'] ?? $modelName,
            'temperature' => $passConfig['temperature'] ?? config('ai.default.temperature'),
            'messages' => json_decode($prompt, true),
        ];

        // Set the correct token limit parameter
        if ($tokenLimitParam === 'max_completion_tokens') {
            $aiParams['max_completion_tokens'] = $passConfig[$tokenLimitParam] ?? $modelConfig['max_tokens'] ?? config('ai.default.max_tokens');
        } else {
            $aiParams['max_tokens'] = $passConfig[$tokenLimitParam] ?? $modelConfig['max_tokens'] ?? config('ai.default.max_tokens');
        }

        // Log the parameters being sent (excluding sensitive information)
        Log::debug("AnalysisPassService: Performing AI operation for pass '{$passName}' with parameters: ".json_encode([
            'model' => $aiParams['model'],
            $tokenLimitParam => $aiParams[$tokenLimitParam],
            'temperature' => $aiParams['temperature'],
            'messages' => '<<omitted>>', // To avoid logging sensitive data
        ]));

        // Perform AI operation
        Log::debug("AnalysisPassService: Performing AI operation for pass '{$passName}'.");
        $responseData = $this->openAIService->performOperation(
            operationIdentifier: $operationIdentifier,
            params: $aiParams
        );
        Log::debug("AnalysisPassService: AI operation response for pass '{$passName}': ".json_encode($responseData));

        // Cache the response for 24 hours
        Cache::put($cacheKey, $responseData, now()->addDay());

        return [
            'prompt' => $prompt,
            'response_data' => $responseData,
        ];
    }

    /**
     * Extract usage metrics from the AI response.
     */
    private function extractUsageMetrics(): array
    {
        Log::debug('AnalysisPassService: Extracting usage metrics.');
        $usage = $this->openAIService->getLastUsage();
        $metadata = [];

        if (! empty($usage)) {
            Log::debug('AnalysisPassService: Usage metrics found: '.json_encode($usage));
            $metadata['usage'] = $usage;
            // Compute cost
            $COST_PER_1K_TOKENS = env('OPENAI_COST_PER_1K_TOKENS', 0.002); // e.g., $0.002 per 1k tokens
            $totalTokens = $usage['total_tokens'] ?? 0;
            $metadata['cost_estimate_usd'] = round(($totalTokens / 1000) * $COST_PER_1K_TOKENS, 6);
            Log::debug("AnalysisPassService: Cost estimate based on tokens: {$metadata['cost_estimate_usd']} USD.");
        } else {
            Log::warning('AnalysisPassService: No usage metrics found.');
        }

        return $metadata;
    }

    /**
     * Create an AIResult entry in the database.
     */
    private function createAiResultEntry(CodeAnalysis $analysis, string $passName, string $prompt, string $responseData, array $metadata): void
    {
        Log::debug("AnalysisPassService: Creating AIResult entry for pass '{$passName}'.");

        try {
            $aiResult = AIResult::create([
                'code_analysis_id' => $analysis->id,
                'pass_name' => $passName,
                'prompt_text' => $prompt,
                'response_text' => $responseData,
                'metadata' => $metadata,
            ]);

            if ($aiResult) {
                Log::info("AnalysisPassService: AIResult entry created with ID {$aiResult->id} for pass '{$passName}'.");
            } else {
                Log::error("AnalysisPassService: Failed to create AIResult entry for pass '{$passName}'.");
            }
        } catch (Exception $exception) {
            Log::error("AnalysisPassService: Exception while creating AIResult for pass '{$passName}': {$exception->getMessage()}", [
                'exception' => $exception,
            ]);
            throw $exception;
        }
    }

    /**
     * Retrieve the raw code from the file path.
     */
    private function getRawCode(string $filePath): string
    {
        if (file_exists($filePath)) {
            Log::debug("AnalysisPassService: Retrieving raw code from '{$filePath}'.");

            return file_get_contents($filePath);
        }

        Log::warning("AnalysisPassService: Raw code file '{$filePath}' does not exist.");

        return '';
    }

    /**
     * Retrieve previous analysis results.
     */
    private function getPreviousResults(CodeAnalysis $analysis): string
    {
        Log::debug("AnalysisPassService: Retrieving previous analysis results for CodeAnalysis ID {$analysis->id}.");

        return $analysis->aiResults()
            ->whereIn('operation_identifier', OperationIdentifier::cases())
            ->orderBy('id', 'asc')
            ->pluck('response_text')
            ->implode("\n\n---\n\n");
    }
}
