<?php

namespace App\Jobs;

use App\Models\CodeAnalysis;
use App\Services\AI\AiderServiceInterface;
use App\Services\AI\OpenAIService;
use App\Services\AnalysisPassService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Handles the processing of AI passes for code analysis.
 *
 * This job retrieves the specified CodeAnalysis record, performs the designated AI pass,
 * stores the results, and marks the pass as completed.
 */
class ProcessAnalysisPassJob implements ShouldBeUnique, ShouldQueue
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
        // Removed duplicate assignment of $this->aiderService
        $this->analysisPassService = app(AnalysisPassService::class);
    }

    /**
     * The number of seconds after which the unique lock will be released.
     */
    public int $uniqueFor = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->analysisPassService->processPass($this->codeAnalysisId, $this->passName, $this->dryRun);
    }

    /**
     * Get the unique identifier for the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return "{$this->codeAnalysisId}-{$this->passName}-".($this->dryRun ? '1' : '0');
    }
}
