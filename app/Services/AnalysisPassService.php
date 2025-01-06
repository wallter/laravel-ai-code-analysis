<?php

namespace App\Services;

use App\Enums\OperationIdentifier;
use App\Models\AIResult;
use App\Models\CodeAnalysis;
use App\Services\AI\AIPromptBuilder;
use App\Services\AI\OpenAIService;
use Exception;
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
                $completedPasses = (array) ($analysis->completed_passes ?? []);

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
        $operationIdentifier = OperationIdentifier::from($passConfig['operation_identifier']);
        $promptBuilder = new AIPromptBuilder(
            $operationIdentifier,
            $passConfig,
            $analysis->ast ?? [],
            $this->getRawCode($analysis->file_path),
            $this->getPreviousResults($analysis)
        );

        // Build prompt
        Log::debug("AnalysisPassService: Building prompt for pass '{$passName}'.");
        $prompt = $promptBuilder->buildPrompt();
        Log::debug("AnalysisPassService: Prompt built for pass '{$passName}': {$prompt}");

        // Perform AI operation
        Log::debug("AnalysisPassService: Performing AI operation for pass '{$passName}'.");
        $responseData = $this->openAIService->performOperation(
            operationIdentifier: $operationIdentifier,
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
     * Retrieve the raw code from the file path.
     */
    private function getRawCode(string $filePath): string
    {
        return file_exists($filePath) ? file_get_contents($filePath) : '';
    }

    /**
     * Retrieve previous analysis results.
     */
    private function getPreviousResults(CodeAnalysis $analysis): string
    {
        return $analysis->aiResults()
            ->whereIn('operation_identifier', OperationIdentifier::cases())
            ->orderBy('id', 'asc')
            ->pluck('response_text')
            ->implode("\n\n---\n\n");
    }
}
