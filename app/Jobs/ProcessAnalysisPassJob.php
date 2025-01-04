<?php

namespace App\Jobs;

use App\Models\CodeAnalysis;
use App\Models\AIResult;
use App\Services\AI\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProcessAnalysisPassJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $codeAnalysisId,
        protected string $passName,
        protected bool $dryRun = false
    ) {
        // You could store pass config or other data here if needed
    }

    /**
     * The job handle method. This is where the AI pass is executed.
     */
    public function handle(OpenAIService $openAIService): void
    {
        // 1) Retrieve CodeAnalysis from DB
        $analysis = CodeAnalysis::find($this->codeAnalysisId);

        if (!$analysis) {
            Log::warning("ProcessAnalysisPassJob: CodeAnalysis [{$this->codeAnalysisId}] not found. Skipping.");
            return;
        }

        $completedPasses = (array) ($analysis->completed_passes ?? []);
        if (in_array($this->passName, $completedPasses, true)) {
            Log::info("ProcessAnalysisPassJob: Pass [{$this->passName}] already completed for [{$analysis->file_path}]. Skipping.");
            return;
        }

        // 2) Retrieve pass config
        $allPassConfigs = config('ai.operations.multi_pass_analysis', []);
        $config         = $allPassConfigs[$this->passName] ?? null;
        if (!$config) {
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
        $prompt = app('App\Services\AI\CodeAnalysisService')->buildPromptForPass($analysis, $this->passName);

        // 5) Perform AI call
        try {
            Log::info("ProcessAnalysisPassJob: Calling OpenAI for pass [{$this->passName}] on [{$analysis->file_path}].");
            $responseData = $openAIService->performOperation(
                operationIdentifier: $config['operation'] ?? 'code_analysis',
                params: [
                    'prompt'      => $prompt,
                    'max_tokens'  => $config['max_tokens']  ?? 1500,
                    'temperature' => $config['temperature'] ?? 0.5,
                ]
            );

            // 6) If we want token usage, parse usage from the response
            //    We'll assume 'usage' is in the $openAIService->lastUsage or we can store it in $responseData
            //    after we adapt OpenAIService (see below).
            $usage    = $openAIService->getLastUsage(); // We'll add a getLastUsage() method
            $metadata = [];
            if (!empty($usage)) {
                $metadata['usage'] = $usage;
                // Optionally compute cost
                $COST_PER_1K_TOKENS = env('OPENAI_COST_PER_1K_TOKENS', 0.002); // e.g., $0.002 per 1k tokens
                $totalTokens = $usage['total_tokens'] ?? 0;
                $metadata['cost_estimate_usd'] = round(($totalTokens / 1000) * $COST_PER_1K_TOKENS, 6);
            }

            // 7) Create AIResult entry
            AIResult::create([
                'code_analysis_id' => $analysis->id,
                'pass_name'        => $this->passName,
                'prompt_text'      => $prompt,
                'response_text'    => $responseData,
                'metadata'         => $metadata,
            ]);

            // 8) Mark pass as complete
            $done = (array) $analysis->completed_passes;
            $done[] = $this->passName;
            $analysis->completed_passes = array_values($done);
            $analysis->current_pass += 1;
            $analysis->save();

            Log::info("ProcessAnalysisPassJob: Completed pass [{$this->passName}] for [{$analysis->file_path}].");
        } catch (\Throwable $e) {
            Log::error("ProcessAnalysisPassJob: Error => " . $e->getMessage(), ['exception' => $e]);
        }
    }
}