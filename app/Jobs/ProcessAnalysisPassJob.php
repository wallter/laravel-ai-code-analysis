<?php

namespace App\Jobs;

use App\Enums\OperationIdentifier;
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

    /**
     * The number of seconds after which the unique lock will be released.
     */
    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        /**
         * The CodeAnalysis ID.
         */
        protected int $codeAnalysisId,
        /**
         * The OperationIdentifier ENUM instance.
         */
        protected OperationIdentifier $passName,
        /**
         * Indicates if the job is a dry run.
         */
        protected bool $dryRun = false
    ) {}

    /**
     * Get the unique identifier for the job.
     */
    public function uniqueId(): string
    {
        return "{$this->codeAnalysisId}-{$this->passName->value}-".($this->dryRun ? '1' : '0');
    }

    /**
     * Execute the job.
     */
    public function handle(AnalysisPassService $analysisPassService): void
    {
        $analysisPassService->processPass($this->codeAnalysisId, $this->passName->value, $this->dryRun);
    }
}
