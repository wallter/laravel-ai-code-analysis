<?php

namespace App\Jobs;

use App\Models\AIResult;
use App\Models\AIScore;
use App\Models\CodeAnalysis;
use App\Services\AnalysisPassService;
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
    protected AnalysisPassService $analysisPassService;

    public function __construct(
        protected int $codeAnalysisId,
        protected string $passName,
        protected bool $dryRun = false
    ) {
        $this->aiderService = app(AiderServiceInterface::class);
        $this->aiderService = app(AiderServiceInterface::class);
        $this->analysisPassService = app(AnalysisPassService::class);
    }

    /**
     * Execute the job.
     *
     * @param  OpenAIService  $openAIService  The service handling OpenAI interactions.
     */
    public function handle(OpenAIService $openAIService): void
    {
        $this->analysisPassService->processPass($this->codeAnalysisId, $this->passName, $this->dryRun);
    }
}
