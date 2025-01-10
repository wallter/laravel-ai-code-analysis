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
 *
 * @package App\Jobs
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
    public int $tries = 3;

    /**
     * The number of seconds before the job should be retried.
     *
     * @var int
     */
    public int $backoff = 60;

    /**
     * The number of seconds after which the unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 300;

    /**
     * The CodeAnalysis ID.
     *
     * @var int
     */
    protected int $codeAnalysisId;

    /**
     * Indicates if the job is a dry run.
     *
     * @var bool
     */
    protected bool $dryRun;

    /**
     * Create a new job instance.
     *
     * @param int  $codeAnalysisId The CodeAnalysis ID.
     * @param bool $dryRun         Indicates if the job is a dry run.
     */
    public function __construct(
        int $codeAnalysisId,
        bool $dryRun = false
    ) {
        $this->codeAnalysisId = $codeAnalysisId;
        $this->dryRun = $dryRun;
    }

    /**
     * Get the unique identifier for the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return "{$this->codeAnalysisId}-" . ($this->dryRun ? '1' : '0');
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $passOrder = Config::get('ai.operations.multi_pass_analysis.pass_order', []);

            if (empty($passOrder)) {
                Log::warning("ProcessAnalysisPassJob: No pass_order defined in config for CodeAnalysis ID {$this->codeAnalysisId}.");

                return;
            }

            $jobs = collect($passOrder)->map(function (string $passName) {
                return new ProcessIndividualPassJob($this->codeAnalysisId, $passName, $this->dryRun);
            })->all();

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
