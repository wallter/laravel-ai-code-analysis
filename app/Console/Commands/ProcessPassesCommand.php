<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CodeAnalysis;
use App\Services\AI\CodeAnalysisService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Context;

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

        // Set dryRun context
        Context::add('dryRun', $dryRun);

        // Retrieve pass order from configuration
        $passOrder = config('ai.operations.multi_pass_analysis.pass_order', []);
        $passOrderCount = count($passOrder);

        // Log the retrieved pass order for debugging
        Context::add('passOrder', $passOrder);
        Context::add('passOrderCount', $passOrderCount);
        Log::info('Retrieved pass_order from config.');

        // Fetch all analyses that have pending passes by filtering in PHP
        $pendingAnalyses = CodeAnalysis::all()
            ->filter(function ($analysis) use ($passOrderCount) {
                Context::add('passOrderCount', $passOrderCount);
                Context::add('current_pass', $analysis->current_pass);
                Context::add('completed_passes', $analysis->completed_passes ?? []);
                Log::info("Checking analysis: {$analysis->file_path}");
                return count((array) ($analysis->completed_passes ?? [])) < $passOrderCount;
            });

        if ($pendingAnalyses->isEmpty()) {
            $this->info("No pending passes to process.");
            Log::info("No pending passes to process.");
            // Remove context after processing
            Context::forget('file_path');
            Context::forget('current_pass');
            return 0;
        }

        // Remove dryRun context
        Context::forget('dryRun');

        Log::info("Pass processing command started.", ['dryRun' => $dryRun]);

        foreach ($pendingAnalyses as $analysis) {
            // Set context for each analysis
            Context::add('file_path', $analysis->file_path);
            Context::add('current_pass', $analysis->current_pass + 1);

            Context::add('file_path', $analysis->file_path);
            Context::add('current_pass', $analysis->current_pass + 1);
            Log::info("Starting pass processing for file: {$analysis->file_path}");
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
            Log::info("Pass processing command executed in dry-run mode.");
        } else {
            $this->info("Pass processing completed.");
            Log::info("Pass processing command executed successfully.");
        }
        // Remove dryRun context after all processing
        Context::forget('dryRun');
        return 0;
    }
}
