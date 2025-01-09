<?php

namespace App\Jobs;

use App\Services\AnalysisPassService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessIndividualPassJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The CodeAnalysis ID.
     *
     * @var int
     */
    public int $codeAnalysisId;

    /**
     * The name of the AI pass to execute.
     *
     * @var string
     */
    public string $passName;

    /**
     * Indicates if the job is a dry run.
     *
     * @var bool
     */
    public bool $dryRun;

    /**
     * Create a new job instance.
     *
     * @param int $codeAnalysisId
     * @param string $passName
     * @param bool $dryRun
     */
    public function __construct(
        int $codeAnalysisId,
        string $passName,
        bool $dryRun = false
    ) {
        $this->codeAnalysisId = $codeAnalysisId;
        $this->passName = $passName;
        $this->dryRun = $dryRun;
    }

    /**
     * Execute the job.
     *
     * @param AnalysisPassService $analysisPassService
     * @return void
     */
    public function handle(AnalysisPassService $analysisPassService): void
    {
        try {
            Log::info("ProcessIndividualPassJob: Starting pass '{$this->passName}' for CodeAnalysis ID {$this->codeAnalysisId}.");

            $analysisPassService->processPass($this->passName, $this->codeAnalysisId, $this->dryRun);

            Log::info("ProcessIndividualPassJob: Completed pass '{$this->passName}' for CodeAnalysis ID {$this->codeAnalysisId}.");

        } catch (Throwable $throwable) {
            Log::error("ProcessIndividualPassJob: Failed pass '{$this->passName}' for CodeAnalysis ID {$this->codeAnalysisId}. Error: {$throwable->getMessage()}", [
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }
}
