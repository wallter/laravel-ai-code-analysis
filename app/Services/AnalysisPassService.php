<?php

namespace App\Services;

use App\Models\AIResult;
use App\Models\CodeAnalysis;
use App\Services\AI\OpenAIService;
use App\Services\AI\CodeAnalysisService;
use App\Services\AI\AiderServiceInterface;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalysisPassService
{
    public function __construct(
        protected OpenAIService $openAIService,
        protected CodeAnalysisService $codeAnalysisService,
        protected AiderServiceInterface $aiderService
    ) {}

    /**
     * Process an analysis pass.
     *
     * @param int $codeAnalysisId
     * @param string $passName
     * @param bool $dryRun
     * @return void
     */
    public function processPass(int $codeAnalysisId, string $passName, bool $dryRun): void
    {
        // 1. Retrieve CodeAnalysis from DB
        $analysis = CodeAnalysis::find($codeAnalysisId);

        if (! $analysis) {
            Log::warning("AnalysisPassService: CodeAnalysis [{$codeAnalysisId}] not found. Skipping.");
            return;
        }

        $completedPasses = (array) ($analysis->completed_passes ?? []);
        if (in_array($passName, $completedPasses, true)) {
            Log::info("AnalysisPassService: Pass [{$passName}] already completed for [{$analysis->file_path}]. Skipping.");
            return;
        }

        // 2. Retrieve pass config
        $passConfigs = config('ai.passes', []);
        $cfg = $passConfigs[$passName] ?? null;
        if (! $cfg) {
            Log::warning("AnalysisPassService: No config for pass [{$passName}]. Skipping.");
            return;
        }

        // 3. If dry-run, skip actual AI call
        if ($dryRun) {
            Log::info("[DRY-RUN] => would run pass [{$passName}] for [{$analysis->file_path}].");
            return;
        }

        // 4. Build prompt
        Log::info("AnalysisPassService: Building prompt for pass [{$passName}].");
        $prompt = $this->codeAnalysisService->buildPromptForPass($analysis, $passName);

        // 5. Perform AI call
        try {
            Log::info("AnalysisPassService: Calling OpenAI for pass [{$passName}] on [{$analysis->file_path}].");
            $responseData = $this->openAIService->performOperation(
                operationIdentifier: $cfg['operation_identifier'] ?? 'code_analysis',
                params: [
                    'prompt' => $prompt,
                    'max_tokens' => $cfg['max_tokens'] ?? 1500,
                    'temperature' => $cfg['temperature'] ?? 0.5,
                ]
            );

            // 6. Extract usage
            $usage = $this->openAIService->getLastUsage();
            $metadata = [];
            if (! empty($usage)) {
                $metadata['usage'] = $usage;
                // Optionally compute cost
                $COST_PER_1K_TOKENS = env('OPENAI_COST_PER_1K_TOKENS', 0.002); // e.g., $0.002 per 1k tokens
                $totalTokens = $usage['total_tokens'] ?? 0;
                $metadata['cost_estimate_usd'] = round(($totalTokens / 1000) * $COST_PER_1K_TOKENS, 6);
            }

            // 7. Create AIResult entry
            AIResult::create([
                'code_analysis_id' => $analysis->id,
                'pass_name' => $passName,
                'prompt_text' => $prompt,
                'response_text' => $responseData,
                'metadata' => $metadata,
            ]);

            // 8. If this is the scoring pass, compute and store scores
            if ($passName === 'scoring_pass') {
                $this->codeAnalysisService->computeAndStoreScores($analysis);
            }

            // 9. Mark pass as complete
            $done = (array) $analysis->completed_passes;
            $done[] = $passName;
            $analysis->completed_passes = array_values($done);
            $analysis->current_pass += 1;
            $analysis->save();

            Log::info("AnalysisPassService: Completed pass [{$passName}] for [{$analysis->file_path}].");
        } catch (\Throwable $throwable) {
            Log::error('AnalysisPassService: Error => '.$throwable->getMessage(), ['exception' => $throwable]);
        }
    }
}
