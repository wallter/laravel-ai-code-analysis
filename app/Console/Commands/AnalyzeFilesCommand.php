<?php

namespace App\Console\Commands;

use App\Models\CodeAnalysis;
use App\Services\AI\CodeAnalysisService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Example command to parse files, store them in code_analyses,
 * then run multi-pass AI, storing each pass in ai_results.
 */
class AnalyzeFilesCommand extends FilesCommand
{
    protected $signature = 'analyze:files
        {--output-file= : Export to .json}
        {--limit-class= : (Unused in this example, but provided by FilesCommand)}
        {--limit-method= : (Unused in this example, but provided)}
        {--dry-run : Skip saving AI results to DB.}';

    protected $description = 'Collect .php files, parse them, run multi-pass AI.';

    public function __construct(protected CodeAnalysisService $analysisService)
    {
        parent::__construct();
    }

    protected function executeCommand(): int
    {
        $outputFile = $this->getOutputFile();
        $dryRun     = (bool) $this->option('dry-run');

        // 1) Collect files
        $phpFiles = $this->analysisService->getParserService()->collectPhpFiles();
        $this->info("Found [{$phpFiles->count()}] .php files.");
        Log::info("AnalyzeFilesCommand: Found [{$phpFiles->count()}] .php files.");

        if ($phpFiles->isEmpty()) {
            $this->warn("No files found. Aborting...");
            Log::warning("AnalyzeFilesCommand: No .php files found, aborting analysis.");
            return 0;
        }

        // 2) Analyze each file
        $results = collect();
        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        foreach ($phpFiles as $filePath) {
            try {
                Log::info("AnalyzeFilesCommand: Analyzing [{$filePath}].");
                $analysisRecord = $this->analysisService->analyzeFile($filePath);
                $this->analysisService->runAnalysis($analysisRecord, $dryRun);

                // Build summary for final output
                $results->push([
                    'file'            => $analysisRecord->file_path,
                    'analysis'        => $analysisRecord->analysis,
                    'current_pass'    => $analysisRecord->current_pass,
                    'completed_passes'=> $analysisRecord->completed_passes,
                ]);
            } catch (\Throwable $e) {
                Log::error("AnalyzeFilesCommand: analysis failed [{$filePath}]: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
                $this->warn("Could not analyze [{$filePath}]: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // 3) (Optional) Export JSON
        if ($outputFile) {
            $this->exportToJson($results->toArray(), $outputFile);
        }

        $this->info("Done! Processed [{$results->count()}] file(s).");
        Log::info("AnalyzeFilesCommand: Completed. Processed [{$results->count()}] files.");
        return 0;
    }

    protected function exportToJson(array $data, string $filePath)
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!$json) {
            $this->warn("Failed to encode JSON: " . json_last_error_msg());
            Log::warning("AnalyzeFilesCommand: Failed to encode JSON output.", [
                'error' => json_last_error_msg()
            ]);
            return;
        }
        @mkdir(dirname($filePath), 0777, true);
        file_put_contents($filePath, $json);
        $this->info("Wrote analysis to [{$filePath}].");
        Log::info("AnalyzeFilesCommand: Wrote analysis JSON to [{$filePath}].");
    }
}