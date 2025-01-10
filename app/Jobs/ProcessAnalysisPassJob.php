<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Handles the processing of AI passes for code analysis.
 *
 * This job retrieves the specified CodeAnalysis record, performs all designated AI passes,
 * stores the results, and marks the passes as completed.
 */
class ProcessAnalysisPassJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds before the job should be retried.
     *
     * @var int
     */
    public $backoff = 60;

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
         * Indicates if the job is a dry run.
         */
        protected bool $dryRun = false

    ) {}

    /**
     * Get the unique identifier for the job.
     */
    public function uniqueId(): string
    {
        return "{$this->codeAnalysisId}-".($this->dryRun ? '1' : '0');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $passOrder = Config::get('ai.operations.multi_pass_analysis.pass_order', []);

            if (empty($passOrder)) {
                Log::warning("ProcessAnalysisPassJob: No pass_order defined in config for CodeAnalysis ID {$this->codeAnalysisId}.");

                return;
            }

            $jobs = [];

            foreach ($passOrder as $passName) {
                $jobs[] = new ProcessIndividualPassJob($this->codeAnalysisId, $passName, $this->dryRun);
            }

            // Dispatch the first job and chain the rest
            $firstJob = array_shift($jobs);
            if ($firstJob) {
                $firstJob->withChain($jobs)->dispatch();
                Log::info("ProcessAnalysisPassJob: Dispatched chain of AI passes for CodeAnalysis ID {$this->codeAnalysisId}.");
            }

        } catch (Throwable $throwable) {
            // Log the exception and optionally retry or mark the job as failed
            Log::error("ProcessAnalysisPassJob: Failed for CodeAnalysis ID {$this->codeAnalysisId}. Error: {$throwable->getMessage()}", [
                'exception' => $throwable,
            ]);

            // Optionally, you can rethrow to let Laravel handle the retry logic
            throw $throwable;
        }
    }
}
