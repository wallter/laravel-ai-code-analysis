<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CodeAnalysis;
use App\Services\AI\CodeAnalysisService;
use Illuminate\Support\Facades\Log;

class ProcessPassesCommand extends Command
{
    protected $signature = 'passes:process';
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
        // Fetch all analyses that have pending passes
        $pendingAnalyses = CodeAnalysis::whereRaw(
            'JSON_LENGTH(completed_passes) < JSON_LENGTH(?)', 
            [
                json_encode(
                    config('ai.operations.multi_pass_analysis.pass_order', [])
                )
            ]
        )->get();

        if ($pendingAnalyses->isEmpty()) {
            $this->info("No pending passes to process.");
            return 0;
        }

        info("Pass processing command started.");
        
        foreach ($pendingAnalyses as $analysis) {
            info("Starting pass processing for file: {$analysis->file_path}");
            $this->info("Processing next pass for [{$analysis->file_path}]...");
            try {
                $this->codeAnalysisService->processNextPass($analysis);
                $this->info("Processed next pass for [{$analysis->file_path}].");
                info("Completed pass processing for file: {$analysis->file_path}");
            } catch (\Throwable $e) {
                Log::error("Error processing pass for file: {$analysis->file_path}", ['exception' => $e]);
                $this->error("Failed to process pass for [{$analysis->file_path}]: {$e->getMessage()}");
                // Optionally, log and continue
            }
        }

        $this->info("Pass processing completed.");
        
        info("Pass processing command executed successfully.");
        return 0;
    }
}
