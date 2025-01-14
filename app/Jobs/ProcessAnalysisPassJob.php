<?php

declare(strict_types=1);

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
 * Class ProcessAnalysisPassJob
 *
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
     */
    public int $tries = 3;

    /**
     * The number of seconds before the job should be retried.
     */
    public int $backoff = 60;

    /**
     * The number of seconds after which the unique lock will be released.
     */
    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     *
     * @param  int  $codeAnalysisId  The CodeAnalysis ID.
     * @param  bool  $dryRun  Indicates if the job is a dry run.
     */
    public function __construct(protected int $codeAnalysisId, protected bool $dryRun = false) {}

    /**
     * Get the unique identifier for the job.
     */
    public function uniqueId(): string
    {
        return "{$this->codeAnalysisId}-".($this->dryRun ? '1' : '0');
    }

    /**
     * Execute the job.
     *
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $passOrder = Config::get('ai.ai.passes.pass_order.pass_order', []);

            if (empty($passOrder)) {
                Log::warning("ProcessAnalysisPassJob: No pass_order defined in config for CodeAnalysis ID {$this->codeAnalysisId}.");

                return;
            }

            $jobs = collect($passOrder)->map(fn (string $passName) => new ProcessIndividualPassJob($this->codeAnalysisId, $passName, $this->dryRun))->all();

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
