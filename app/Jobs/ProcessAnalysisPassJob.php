<?php

namespace App\Jobs;

use App\Models\AIResult;
use App\Models\AIScore;
use App\Models\CodeAnalysis;
use App\Services\AI\OpenAIService;
use App\Services\AI\AiderServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Handles the processing of AI passes for code analysis.
 *
 * This job retrieves the specified CodeAnalysis record, performs the designated AI pass,
 * stores the results, and marks the pass as completed.
 */
class ProcessAnalysisPassJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    protected AiderServiceInterface $aiderService;

    public function __construct(
        protected int $codeAnalysisId,
        protected string $passName,
        protected bool $dryRun = false
    ) {
        $this->aiderService = app(AiderServiceInterface::class);
        // You could store pass config or other data here if needed
    }

    /**
     * Execute the job.
     *
     * @param  OpenAIService  $openAIService  The service handling OpenAI interactions.
     */
    public function handle(OpenAIService $openAIService): void
    {
        // 1) Retrieve CodeAnalysis from DB
        $analysis = CodeAnalysis::find($this->codeAnalysisId);

        if (! $analysis) {
            Log::warning("ProcessAnalysisPassJob: CodeAnalysis [{$this->codeAnalysisId}] not found. Skipping.");

            return;
        }

        $completedPasses = (array) ($analysis->completed_passes ?? []);
        if (in_array($this->passName, $completedPasses, true)) {
            Log::info("ProcessAnalysisPassJob: Pass [{$this->passName}] already completed for [{$analysis->file_path}]. Skipping.");

            return;
        }

        // 2) Retrieve pass config
        $passConfigs = config('ai.passes', []);
        $cfg = $passConfigs[$this->passName] ?? null;
        if (! $cfg) {
            Log::warning("ProcessAnalysisPassJob: No config for pass [{$this->passName}]. Skipping.");

            return;
        }

        // 3) If dry-run, skip actual AI call
        if ($this->dryRun) {
            Log::info("[DRY-RUN] => would run pass [{$this->passName}] for [{$analysis->file_path}].");

            return;
        }

        // 4) Build prompt
        Log::info("ProcessAnalysisPassJob: Building prompt for pass [{$this->passName}].");
        $codeAnalysisService = app(\App\Services\AI\CodeAnalysisService::class);
        $prompt = $codeAnalysisService->buildPromptForPass($analysis, $this->passName);

        // 5) Perform AI call
        try {
            Log::info("ProcessAnalysisPassJob: Calling OpenAI for pass [{$this->passName}] on [{$analysis->file_path}].");
            $responseData = $openAIService->performOperation(
                operationIdentifier: $cfg['operation_identifier'] ?? 'code_analysis',
                params: [
                    'prompt' => $prompt,
                    'max_tokens' => $config['max_tokens'] ?? 1500,
                    'temperature' => $config['temperature'] ?? 0.5,
                ]
            );

            // 6) Extract usage
            $usage = $openAIService->getLastUsage();
            $metadata = [];
            if (! empty($usage)) {
                $metadata['usage'] = $usage;
                // Optionally compute cost
                $COST_PER_1K_TOKENS = env('OPENAI_COST_PER_1K_TOKENS', 0.002); // e.g., $0.002 per 1k tokens
                $totalTokens = $usage['total_tokens'] ?? 0;
                $metadata['cost_estimate_usd'] = round(($totalTokens / 1000) * $COST_PER_1K_TOKENS, 6);
            }

            // 7) Create AIResult entry
            AIResult::create([
                'code_analysis_id' => $analysis->id,
                'pass_name' => $this->passName,
                'prompt_text' => $prompt,
                'response_text' => $responseData,
                'metadata' => $metadata,
            ]);

            // If this is the scoring pass, compute and store scores
            if ($this->passName === 'scoring_pass') {
                $codeAnalysisService = app(\App\Services\AI\CodeAnalysisService::class);
                $codeAnalysisService->computeAndStoreScores($analysis);
            }

            // 8) Mark pass as complete
            $done = (array) $analysis->completed_passes;
            $done[] = $this->passName;
            $analysis->completed_passes = array_values($done);
            $analysis->current_pass += 1;
            $analysis->save();

            Log::info("ProcessAnalysisPassJob: Completed pass [{$this->passName}] for [{$analysis->file_path}].");
        } catch (\Throwable $throwable) {
            Log::error('ProcessAnalysisPassJob: Error => '.$throwable->getMessage(), ['exception' => $throwable]);
        }
    }
}
