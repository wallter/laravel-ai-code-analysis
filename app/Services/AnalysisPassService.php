<?php

namespace App\Services;

use App\Models\AIResult;
use App\Models\CodeAnalysis;
// use App\Services\AI\AiderServiceInterface;
use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;

class AnalysisPassService
{
    public function __construct(
        protected OpenAIService $openAIService,
        protected CodeAnalysisService $codeAnalysisService,
        // protected AiderServiceInterface $aiderService // Not Used ... yet
    ) {}

    /**
     * Process an analysis pass.
     */
    public function processPass(int $codeAnalysisId, string $passName, bool $dryRun): void
    {
        try {
            $analysis = $this->retrieveAnalysis($codeAnalysisId);
            if (! $analysis) {
                return;
            }

            if ($this->isPassCompleted($analysis, $passName)) {
                return;
            }

            $passConfig = $this->getPassConfig($passName);
            if (! $passConfig) {
                return;
            }

            if ($this->handleDryRun($passName, $analysis, $dryRun)) {
                return;
            }

            $aiResult = $this->executeAiOperation($analysis, $passName, $passConfig);
            $metadata = $this->extractUsageMetrics();

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
            Log::error('AnalysisPassService: Error => '.$throwable->getMessage(), ['exception' => $throwable]);
        }
    }

    /**
     * Retrieve the CodeAnalysis instance from the database.
     */
    private function retrieveAnalysis(int $codeAnalysisId): ?CodeAnalysis
    {
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
        Log::info("AnalysisPassService: Building prompt for pass [{$passName}].");
        $prompt = $this->codeAnalysisService->buildPromptForPass($analysis, $passName);

        // Perform AI call
        Log::info("AnalysisPassService: Calling OpenAI for pass [{$passName}] on [{$analysis->file_path}].");
        $responseData = $this->openAIService->performOperation(
            operationIdentifier: $passConfig['operation_identifier'] ?? 'code_analysis',
            params: [
                'prompt' => $prompt,
                'max_tokens' => $passConfig['max_tokens'] ?? 1500,
                'temperature' => $passConfig['temperature'] ?? 0.5,
            ]
        );

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
        $usage = $this->openAIService->getLastUsage();
        $metadata = [];

        if (! empty($usage)) {
            $metadata['usage'] = $usage;
            // Compute cost
            $COST_PER_1K_TOKENS = env('OPENAI_COST_PER_1K_TOKENS', 0.002); // e.g., $0.002 per 1k tokens
            $totalTokens = $usage['total_tokens'] ?? 0;
            $metadata['cost_estimate_usd'] = round(($totalTokens / 1000) * $COST_PER_1K_TOKENS, 6);
        }

        return $metadata;
    }

    /**
     * Create an AIResult entry in the database.
     */
    private function createAiResultEntry(CodeAnalysis $analysis, string $passName, string $prompt, string $responseData, array $metadata): void
    {
        AIResult::create([
            'code_analysis_id' => $analysis->id,
            'pass_name' => $passName,
            'prompt_text' => $prompt,
            'response_text' => $responseData,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Handle the scoring pass by computing and storing scores.
     */
    private function handleScoringPass(CodeAnalysis $analysis, string $passName): void
    {
        if ($passName === 'scoring_pass') {
            $this->codeAnalysisService->computeAndStoreScores($analysis);
        }
    }

    /**
     * Mark the pass as completed in the CodeAnalysis instance.
     */
    private function markPassAsCompleted(CodeAnalysis $analysis, string $passName): void
    {
        $done = (array) $analysis->completed_passes;
        $done[] = $passName;
        $analysis->completed_passes = array_values($done);
        $analysis->current_pass += 1;
        $analysis->save();

        Log::info("AnalysisPassService: Completed pass [{$passName}] for [{$analysis->file_path}].");
    }
}
