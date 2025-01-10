<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Services\AnalysisPassService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class ProcessIndividualPassJob
 *
 * Processes a single AI pass for a code analysis.
 *
 * @package App\Jobs
 */
class ProcessIndividualPassJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The CodeAnalysis ID.
     *
     * @var int|null
     */
    public ?int $codeAnalysisId;

    /**
     * The name of the AI pass to execute.
     *
     * @var string|null
     */
    public ?string $passName;

    /**
     * Indicates if the job is a dry run.
     *
     * @var bool
     */
    public bool $dryRun;

    /**
     * Create a new job instance.
     *
     * @param int|null    $codeAnalysisId The CodeAnalysis ID.
     * @param string|null $passName       The name of the AI pass to execute.
     * @param bool        $dryRun         Indicates if the job is a dry run.
     */
    public function __construct(
        ?int $codeAnalysisId = null,
        ?string $passName = null,
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
        if ($this->codeAnalysisId === null || $this->passName === null) {
            Log::error("ProcessIndividualPassJob: Missing required parameters for CodeAnalysis ID {$this->codeAnalysisId}.");

            return;
        }

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
