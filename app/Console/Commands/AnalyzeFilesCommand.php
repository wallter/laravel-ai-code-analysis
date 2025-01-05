<?php

namespace App\Console\Commands;

use App\Services\AI\CodeAnalysisService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Collect .php files, parse them, and run multi-pass AI analysis.
 */
class AnalyzeFilesCommand extends FilesCommand
{
    protected $signature = 'analyze:files
        {--output-file= : Export to .json}
        {--limit-class= : (Unused in this example, but provided by FilesCommand)}
        {--limit-method= : (Unused in this example, but provided)}
        {--dry-run : Skip saving AI results to DB.}';

    protected $description = 'Collect .php files, parse them, run multi-pass AI.';

    /**
     * @var CodeAnalysisService
     */
    public function __construct(protected CodeAnalysisService $analysisService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int Exit status code.
     */
    public function handle(): int
    {
        $outputFile = $this->getOutputFile();
        $dryRun = (bool) $this->option('dry-run');

        $phpFiles = $this->collectPhpFiles();
        if ($phpFiles->isEmpty()) {
            $this->warn('No files found. Aborting...');
            Log::warning('AnalyzeFilesCommand: No .php files found, aborting analysis.');

            return 0;
        }

        $results = $this->processFiles($phpFiles, $dryRun);

        if ($outputFile) {
            $this->exportToJson($results->toArray(), $outputFile);
        }

        $this->info("Done! Processed [{$results->count()}] file(s).");
        Log::info("AnalyzeFilesCommand: Completed. Processed [{$results->count()}] files.");

        return 0;
    }

    /**
     * Collect PHP files using the parser service.
     *
     * @return Collection<string> The collection of PHP file paths.
     */
    protected function collectPhpFiles(): Collection
    {
        $phpFiles = $this->analysisService->getParserService()->collectPhpFiles();
        $this->info("Found [{$phpFiles->count()}] .php files.");
        Log::info("AnalyzeFilesCommand: Found [{$phpFiles->count()}] .php files.");

        return $phpFiles;
    }

    /**
     * Process each PHP file for analysis.
     *
     * @param  Collection<string>  $phpFiles  The collection of PHP file paths.
     * @param  bool  $dryRun  Indicates if the run is a dry run.
     * @return Collection<array> The collection of analysis results.
     */
    protected function processFiles(Collection $phpFiles, bool $dryRun): Collection
    {
        $results = collect();
        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        foreach ($phpFiles as $filePath) {
            try {
                Log::info("AnalyzeFilesCommand: Analyzing [{$filePath}].");
                $analysisRecord = $this->analysisService->analyzeFile($filePath);
                $this->analysisService->runAnalysis($analysisRecord, $dryRun);

                $results->push([
                    'file' => $analysisRecord->file_path,
                    'analysis' => $analysisRecord->analysis,
                    'current_pass' => $analysisRecord->current_pass,
                    'completed_passes' => $analysisRecord->completed_passes,
                ]);
            } catch (Throwable $e) {
                Log::error("AnalyzeFilesCommand: Analysis failed [{$filePath}]: ".$e->getMessage(), [
                    'exception' => $e,
                ]);
                $this->warn("Could not analyze [{$filePath}]: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $results;
    }

    /**
     * Export analysis results to a JSON file.
     *
     * @param  array  $data  The analysis data to export.
     * @param  string  $filePath  The file path to export the JSON to.
     */
    protected function exportToJson(array $data, string $filePath): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! $json) {
            $this->warn('Failed to encode JSON: '.json_last_error_msg());
            Log::warning('AnalyzeFilesCommand: Failed to encode JSON output.', [
                'error' => json_last_error_msg(),
            ]);

            return;
        }

        @mkdir(dirname($filePath), 0777, true);
        file_put_contents($filePath, $json);
        $this->info("Wrote analysis to [{$filePath}].");
        Log::info("AnalyzeFilesCommand: Wrote analysis JSON to [{$filePath}].");
    }
}
