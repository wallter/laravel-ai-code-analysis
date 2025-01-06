<?php

namespace App\Console\Commands;

use App\Models\CodeAnalysis;
use App\Services\AnalysisPassService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Processes AI passes for each CodeAnalysis record that has incomplete passes.
 *
 * This command handles both dry-run and actual execution modes, providing detailed logging and user feedback.
 */
class PassesProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passes:process
        {--dry-run : Run without persisting changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the next AI pass for each CodeAnalysis record with incomplete passes.';

    /**
     * @var AnalysisPassService
     */
    public function __construct(protected AnalysisPassService $analysisPassService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * Determines which CodeAnalysis records require further AI passes and processes them accordingly.
     * Supports both dry-run and actual processing modes, with optional verbose output for debugging.
     *
     * @return int Exit status code.
     */
    public function handle(): int
    {
        // Initialize an array to keep track of queued passes
        $queuedPasses = [];

        // 1) Determine if we’re running in dry-run mode
        $dryRun = (bool) $this->option('dry-run');
        Log::info('PassesProcessCommand started.', ['dryRun' => $dryRun]);

        // 2) Retrieve the pass order from config
        $passOrder = config('ai.operations.multi_pass_analysis.pass_order', []);
        $passOrderCount = count($passOrder);

        $this->info("Found [{$passOrderCount}] passes in pass_order: ".implode(', ', $passOrder));
        Log::debug('Pass order retrieved: '.json_encode($passOrder));

        if ($passOrderCount < 1) {
            $this->warn("No pass_order defined in config('ai.operations.multi_pass_analysis.pass_order').");
            Log::warning('Pass order is empty. Aborting processing.');

            return 0;
        }

        // 3) Gather all CodeAnalysis records
        $allAnalyses = CodeAnalysis::all();
        if ($allAnalyses->isEmpty()) {
            $this->warn("No CodeAnalysis records in DB. Try running 'analyze:files' first.");
            Log::info('No CodeAnalysis found. Stopping.');

            return 0;
        }

        // 4) Identify which analyses still have passes pending
        $pendingAnalyses = $allAnalyses->filter(function ($analysis) use ($passOrderCount) {
            $completed = (array) ($analysis->completed_passes ?? []);
            $doneCount = count($completed);
            $hasMoreToDo = $doneCount < $passOrderCount;

            Log::debug("Check [{$analysis->file_path}]: doneCount={$doneCount}, currentPass={$analysis->current_pass}, hasMoreToDo=".($hasMoreToDo ? 'true' : 'false'));

            return $hasMoreToDo;
        });

        if ($pendingAnalyses->isEmpty()) {
            $this->info('No pending passes to process.');
            Log::info('No pending passes found.');

            return 0;
        }

        // 5) Show how many records we’ll process
        $this->info("Found [{$pendingAnalyses->count()}] file(s) needing additional passes.");
        Log::info("Processing [{$pendingAnalyses->count()}] CodeAnalysis records with pending passes.");

        // Create a progress bar for the pending analyses
        $bar = $this->output->createProgressBar($pendingAnalyses->count());
        $bar->start();

        // We’ll store final statuses to display in a summary table
        $finalStatuses = [];

        // 6) Process each record needing passes
        foreach ($pendingAnalyses as $analysis) {
            // Determine which passes are missing for this analysis
            $completedPasses = (array) ($analysis->completed_passes ?? []);
            $missingPasses = array_filter($passOrder, function($pass) use ($completedPasses) {
                return !in_array($pass, $completedPasses, true);
            });

            if (!empty($missingPasses)) {
                $queuedPasses[$analysis->file_path] = $missingPasses;
            }
                // This runs all missing passes
                $this->analysisPassService->runAnalysis($analysis, $dryRun);

                $completedPasses = collect($analysis->completed_passes)->sort()->values()->all();

                if ($dryRun) {
                    $this->comment("[DRY-RUN] => Would have completed passes for [{$analysis->file_path}]: "
                        .implode(', ', $completedPasses));
                } else {
                    $this->info("Passes now completed for [{$analysis->file_path}]: "
                        .implode(', ', $completedPasses));
                    Log::info("Completed passes for [{$analysis->file_path}]: ".json_encode($completedPasses));
                }

                // Collect final status for table display
                $finalStatuses[] = [
                    'file_path' => $analysis->file_path,
                    'current_pass' => $analysis->current_pass,
                    'completed_passes' => implode(', ', $completedPasses),
                ];
            } catch (\Throwable $e) {
                Log::error("Error processing passes for [{$analysis->file_path}].", [
                    'exception' => $e,
                ]);
                $this->error("Failed to process passes for [{$analysis->file_path}]: {$e->getMessage()}");
            }

            // Advance progress bar for each record
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $bar->finish();
        $this->newLine();

        // Display a summary of all queued jobs
        if (!empty($queuedPasses)) {
            $this->line('Queued Jobs Summary:');
            $summaryRows = [];
            foreach ($queuedPasses as $filePath => $passes) {
                $summaryRows[] = [
                    'File Path' => $filePath,
                    'Queued Passes' => implode(', ', $passes),
                ];
            }
            $this->table(['File Path', 'Queued Passes'], $summaryRows);
        }
        $this->line('Summary of processed records:');
        $this->table(
            ['File Path', 'Current Pass', 'Completed Passes'],
            array_map(fn ($row) => [
                $row['file_path'],
                $row['current_pass'],
                $row['completed_passes'],
            ], $finalStatuses)
        );

        // 8) Wrap up
        if ($dryRun) {
            $this->warn('Dry-run pass processing completed (no DB changes).');
            Log::info('Dry-run pass processing completed, no DB updates.');
        } else {
            $this->info('Pass processing completed for all pending analyses.');
            Log::info('Pass processing completed for all pending analyses.');
        }

        return 0;
    }
}
