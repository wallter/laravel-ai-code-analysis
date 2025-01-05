<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CodeAnalysis;
use App\Services\AI\CodeAnalysisService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Context;

/**
 * ProcessAnalysisPassJob.
 *
 * Handles the processing of individual AI passes for a given CodeAnalysis record.
 *
 * @package App\Console\Commands
 */
class ProcessAnalysisPassJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passes:process
        {--dry-run : Run without persisting changes}
        {--verbose : Show extra debugging info in console output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the next AI pass for each CodeAnalysis record with incomplete passes.';

    /**
     * Constructor for ProcessAnalysisPassJob.
     *
     * Injects the CodeAnalysisService dependency.
     *
     * @param \App\Services\AI\CodeAnalysisService $analysisService Service to handle code analysis operations.
     */
    public function __construct(
        protected CodeAnalysisService $analysisService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * Processes AI passes for CodeAnalysis records, handling both dry-run and actual execution modes.
     *
     * @return int Exit status code.
     */
    public function handle(): int
    {
        // 1) Determine if we’re running in dry-run mode
        $dryRun = (bool) $this->option('dry-run');
        Context::add('dryRun', $dryRun);
        Log::info("ProcessPassesCommand started.", ['dryRun' => $dryRun]);

        // 2) Retrieve the pass order from config
        $passOrder      = config('ai.operations.multi_pass_analysis.pass_order', []);
        $passOrderCount = count($passOrder);

        $this->info("Found [{$passOrderCount}] passes in pass_order: " . implode(', ', $passOrder));
        Log::debug("Passes => " . json_encode($passOrder));

        if ($passOrderCount < 1) {
            $this->warn("No pass_order defined in config('ai.operations.multi_pass_analysis.pass_order').");
            Log::warning("PassOrder is empty. Aborting.");
            Context::forget('dryRun');
            return 0;
        }

        // 3) Gather all CodeAnalysis records
        $allAnalyses = CodeAnalysis::all();
        if ($allAnalyses->isEmpty()) {
            $this->warn("No CodeAnalysis records in DB. Try running 'analyze:files' first.");
            Log::info("No CodeAnalysis found. Stopping.");
            Context::forget('dryRun');
            return 0;
        }

        // 4) Identify which analyses still have passes pending
        $pendingAnalyses = $allAnalyses->filter(function ($analysis) use ($passOrderCount) {
            $completed      = (array) ($analysis->completed_passes ?? []);
            $doneCount      = count($completed);
            $hasMoreToDo    = $doneCount < $passOrderCount;

            Log::debug("Check [{$analysis->file_path}]: doneCount={$doneCount}, currentPass={$analysis->current_pass}, hasMoreToDo=" . ($hasMoreToDo ? 'true' : 'false'));
            return $hasMoreToDo;
        });

        if ($pendingAnalyses->isEmpty()) {
            $this->info("No pending passes to process.");
            Log::info("No pending passes found.");
            Context::forget('dryRun');
            return 0;
        }

        // 5) Show how many records we’ll process
        $this->info("Found [{$pendingAnalyses->count()}] file(s) needing additional passes.");
        Log::info("ProcessPassesCommand: Found {$pendingAnalyses->count()} record(s) needing passes.");

        // Create a progress bar for the pending analyses
        $bar = $this->output->createProgressBar($pendingAnalyses->count());
        $bar->setFormat("verbose");
        $bar->start();

        // We’ll store final statuses to display in a summary table
        $finalStatuses = [];

        // 6) Process each record needing passes
        foreach ($pendingAnalyses as $analysis) {
            // Optional verbose info
            if ($this->option('verbose')) {
                $this->comment(">>> Starting passes for [{$analysis->file_path}]. Already completed: " 
                    . implode(', ', $analysis->completed_passes ?? []));
            }

            try {
                // This runs all missing passes
                $this->analysisService->runAnalysis($analysis, $dryRun);

                $completeList = $analysis->completed_passes ?: [];

                if ($dryRun) {
                    $this->comment("[DRY-RUN] => Would have completed passes for [{$analysis->file_path}]: " 
                        . implode(', ', $completeList));
                } else {
                    $this->info("Passes now completed for [{$analysis->file_path}]: " 
                        . implode(', ', $completeList));
                    Log::info("Completed passes => " . json_encode($completeList));
                }

                // Collect final status for table display
                $finalStatuses[] = [
                    'file_path'      => $analysis->file_path,
                    'current_pass'   => $analysis->current_pass,
                    'completed_pass' => implode(', ', $analysis->completed_passes ?? []),
                ];
            } catch (\Throwable $e) {
                Log::error("Error processing passes for [{$analysis->file_path}].", [
                    'exception' => $e
                ]);
                $this->error("Failed to process passes for [{$analysis->file_path}]: {$e->getMessage()}");
            }

            // Advance progress bar for each record
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // 7) Final summary table
        $this->line("Summary of processed records:");
        $this->table(
            ['File Path', 'Current Pass', 'Completed Passes'],
            array_map(fn($row) => [
                $row['file_path'],
                $row['current_pass'],
                $row['completed_pass'],
            ], $finalStatuses)
        );

        // 8) Wrap up
        if ($dryRun) {
            $this->warn("Dry-run pass processing completed (no DB changes).");
            Log::info("Dry-run pass processing completed, no DB updates.");
        } else {
            $this->info("Pass processing completed for all pending analyses.");
            Log::info("Pass processing completed for all pending analyses.");
        }

        Context::forget('dryRun');
        return 0;
    }
}
