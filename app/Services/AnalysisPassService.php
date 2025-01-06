<?php

namespace App\Services;

use App\Models\AIResult;
use App\Models\CodeAnalysis;
// use App\Services\AI\AiderServiceInterface;
use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for handling analysis passes for code.
 */
class AnalysisPassService
{
    public function __construct(
        protected OpenAIService $openAIService,
        protected CodeAnalysisService $codeAnalysisService
        // protected AiderServiceInterface $aiderService // Not Used ... yet
    ) {}

    /**
     * Process an analysis pass.
     */
    public function processPass(int $codeAnalysisId, string $passName, bool $dryRun): void
    {
        Log::info("AnalysisPassService: Starting processPass for CodeAnalysis ID {$codeAnalysisId}, Pass '{$passName}', dryRun: ".($dryRun ? 'true' : 'false'));

        try {
            $analysis = $this->retrieveAnalysis($codeAnalysisId);
            if (! $analysis) {
                Log::warning("AnalysisPassService: No analysis found for CodeAnalysis ID {$codeAnalysisId}. Exiting processPass.");

                return;
            }

            if ($this->isPassCompleted($analysis, $passName)) {
                Log::info("AnalysisPassService: Pass '{$passName}' already completed for CodeAnalysis ID {$codeAnalysisId}. Exiting processPass.");

                return;
            }

            $passConfig = $this->getPassConfig($passName);
            if (! $passConfig) {
                Log::warning("AnalysisPassService: No configuration found for pass '{$passName}'. Exiting processPass.");

                return;
            }

            if ($this->handleDryRun($passName, $analysis, $dryRun)) {
                Log::info("AnalysisPassService: Dry run handled for pass '{$passName}'. Exiting processPass.");

                return;
            }

            Log::info("AnalysisPassService: Executing AI operation for pass '{$passName}'.");
            $aiResult = $this->executeAiOperation($analysis, $passName, $passConfig);
            Log::info("AnalysisPassService: AI operation completed for pass '{$passName}'.");

            Log::info("AnalysisPassService: Extracting usage metrics for pass '{$passName}'.");
            $metadata = $this->extractUsageMetrics();

            Log::info("AnalysisPassService: Creating AIResult entry for pass '{$passName}'.");
            $this->createAiResultEntry(
                $analysis,
                $passName,
                $aiResult['prompt'],
                $aiResult['response_data'],
                $metadata
            );

            $this->handleScoringPass($analysis, $passName);
            $this->markPassAsCompleted($analysis, $passName);
        } catch (\Throwable $throwable) {
            Log::error('AnalysisPassService: Exception in processPass => '.$throwable->getMessage(), ['exception' => $throwable]);
        }

        Log::info("AnalysisPassService: Finished processPass for CodeAnalysis ID {$codeAnalysisId}, Pass '{$passName}'.");
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
     * Check if the pass has already been completed.
     */
    private function isPassCompleted(CodeAnalysis $analysis, string $passName): bool
    {
        Log::debug("AnalysisPassService: Checking if pass '{$passName}' is completed for CodeAnalysis ID {$analysis->id}.");
        $completedPasses = (array) ($analysis->completed_passes ?? []);
        if (in_array($passName, $completedPasses, true)) {
            Log::info("AnalysisPassService: Pass [{$passName}] already completed for [{$analysis->file_path}]. Skipping.");

            return true;
        }

        return false;
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
            Log::warning("AnalysisPassService: No config for pass [{$passName}]. Skipping.");
        }

        return $cfg;
    }

    /**
     * Handle dry-run scenarios.
     *
     * @return bool Returns true if dry-run is handled and processing should stop.
     */
    private function handleDryRun(string $passName, CodeAnalysis $analysis, bool $dryRun): bool
    {
        if ($dryRun) {
            Log::info("[DRY-RUN] => would run pass [{$passName}] for [{$analysis->file_path}].");

            return true;
        }

        return false;
    }

    /**
     * Execute the AI operation.
     *
     * @return array Contains 'prompt' and 'response_data'.
     */
    private function executeAiOperation(CodeAnalysis $analysis, string $passName, array $passConfig): array
    {
        // Build prompt
        Log::debug("AnalysisPassService: Building prompt for pass '{$passName}'.");
        $prompt = $this->codeAnalysisService->buildPromptForPass($analysis, $passName);
        Log::debug("AnalysisPassService: Prompt built for pass '{$passName}': {$prompt}");

        // Perform AI call
        Log::debug("AnalysisPassService: Performing AI operation for pass '{$passName}'.");
        $responseData = $this->openAIService->performOperation(
            operationIdentifier: $passConfig['operation_identifier'] ?? 'code_analysis',
            params: [
                'prompt' => $prompt,
                'max_tokens' => $passConfig['max_tokens'] ?? 1500,
                'temperature' => $passConfig['temperature'] ?? 0.5,
            ]
        );
        Log::debug("AnalysisPassService: AI operation response for pass '{$passName}': ".json_encode($responseData));

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
    }

    /**
     * Run the analysis by queuing the required passes.
     *
     * @param  CodeAnalysis  $analysis  The CodeAnalysis instance.
     * @param  bool          $dryRun    Whether to perform a dry run.
     * @return void
     */
    public function runAnalysis(CodeAnalysis $analysis, bool $dryRun = false): void
    {
        Log::info("AnalysisPassService: Queueing multi-pass analysis for [{$analysis->file_path}].", [
            'dryRun' => $dryRun,
        ]);

        $completedPasses = (array) ($analysis->completed_passes ?? []);
        $passOrder = config('ai.operations.multi_pass_analysis.pass_order', []);

        foreach ($passOrder as $passName) {
            if (! in_array($passName, $completedPasses, true)) {
                // Dispatch a job for each missing pass
                Log::info("Dispatching ProcessAnalysisPassJob for pass [{$passName}] => [{$analysis->file_path}].");
                ProcessAnalysisPassJob::dispatch(
                    codeAnalysisId: $analysis->id,
                    passName: $passName,
                    dryRun: $dryRun
                );
            }
        }
    }
}
