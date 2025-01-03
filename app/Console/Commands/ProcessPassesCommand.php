<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CodeAnalysis;
use App\Services\AI\CodeAnalysisService;
use Illuminate\Support\Facades\Log;

class ProcessPassesCommand extends Command
{
    protected $signature = 'passes:process {--dry-run : Run the command without persisting any changes}';
    protected $description = 'Process the next AI pass for each CodeAnalysis record.';

    /**
     * Inject the CodeAnalysisService.
     */
    public function __construct(protected CodeAnalysisService $codeAnalysisService)
    {
        parent::__construct();
    }

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Retrieve pass order from configuration
        $passOrder = config('ai.operations.multi_pass_analysis.pass_order', []);
        $passOrderCount = count($passOrder);

        // Log the retrieved pass order for debugging
        Log::info('Retrieved pass_order from config:', ['passOrder' => $passOrder, 'passOrderCount' => $passOrderCount]);

        // Fetch all analyses that have pending passes by filtering in PHP
        $pendingAnalyses = CodeAnalysis::all()
            ->filter(function ($analysis) use ($passOrderCount) {
                Log::info("Checking analysis: {$analysis->file_path}", [
                    'passOrderCount' => $passOrderCount,
                    'current_pass' => $analysis->current_pass,
                    'completed_passes' => $analysis->completed_passes ?? [],
                ]);
                return count((array) ($analysis->completed_passes ?? [])) < $passOrderCount;
            });

        if ($pendingAnalyses->isEmpty()) {
            $this->info("No pending passes to process.");
            Log::info("No pending passes to process.");
            return 0;
        }

        Log::info("Pass processing command started.", ['dryRun' => $dryRun]);

        foreach ($pendingAnalyses as $analysis) {
            Log::info("Starting pass processing for file: {$analysis->file_path}", ['dryRun' => $dryRun]);
            $this->info("Processing next pass for [{$analysis->file_path}]...");
            try {
                $this->codeAnalysisService->processNextPass($analysis, $dryRun);
                if (!$dryRun) {
                    $this->info("Processed next pass for [{$analysis->file_path}].");
                    Log::info("Processed next pass for [{$analysis->file_path}].");
                } else {
                    $this->info("Dry-run: processed next pass for [{$analysis->file_path}].");
                    Log::info("Dry-run: processed next pass for [{$analysis->file_path}].");
                }
                Log::info("Completed pass processing for file: {$analysis->file_path}" . ($dryRun ? " (dry-run)" : ""), ['dryRun' => $dryRun]);
            } catch (\Throwable $e) {
                Log::error("Error processing pass for file: {$analysis->file_path}", ['exception' => $e]);
                $this->error("Failed to process pass for [{$analysis->file_path}]: {$e->getMessage()}");
                // Optionally, log and continue
            }
        }

        if ($dryRun) {
            $this->info("Dry-run pass processing completed.");
            Log::info("Dry-run pass processing completed.");
            Log::info("Pass processing command executed in dry-run mode.", ['dryRun' => $dryRun]);
        } else {
            $this->info("Pass processing completed.");
            Log::info("Pass processing command executed successfully.", ['dryRun' => $dryRun]);
        }
        return 0;
    }
}
