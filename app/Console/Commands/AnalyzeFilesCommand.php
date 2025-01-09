<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAnalysisPassJob;
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
        {--output-file= : Export analysis results to a .json file}
        {--limit-class= : Limit analysis to specific classes (comma-separated)}
        {--limit-method= : (Unused in this example, but provided)}
        {--dry-run : Skip saving AI results to DB.}';

    protected $description = 'Collect .php files, parse them, and run multi-pass AI analysis.';

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
        $outputFile = $this->option('output-file');
        $dryRun = (bool) $this->option('dry-run');
        $limitClassesOption = $this->option('limit-class');
        $limitClasses = [];

        if ($limitClassesOption) {
            $limitClasses = array_map('trim', explode(',', $limitClassesOption));

            // Validate that limitClasses are not empty and are valid class names
            foreach ($limitClasses as $class) {
                if (!preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*$/', $class)) {
                    $this->error("Invalid class name provided in --limit-class: '{$class}'");
                    Log::error("AnalyzeFilesCommand: Invalid class name provided in --limit-class: '{$class}'");
                    return 1;
                }
            }

            $this->info('Limiting analysis to classes: ' . implode(', ', $limitClasses));
            Log::info('AnalyzeFilesCommand: Limiting analysis to classes: ' . implode(', ', $limitClasses));
        }

        // Collect all PHP files (both from folders and files)
        $folders = config('parsing.folders', []);
        $files = config('parsing.files', []);

        $phpFiles = collect();

        foreach ($folders as $folder) {
            // Ensure the folder exists before attempting to collect files
            if (is_dir($folder)) {
                $collected = $this->analysisService->collectPhpFiles($folder);
                $phpFiles = $phpFiles->merge($collected);
            } else {
                $this->warn("Directory does not exist: {$folder}");
                Log::warning("AnalyzeFilesCommand: Directory does not exist: {$folder}");
            }
        }

        foreach ($files as $filePath) {
            if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
                $phpFiles->push($filePath);
            } else {
                $this->warn("File does not exist or is not a PHP file: {$filePath}");
                Log::warning("AnalyzeFilesCommand: File does not exist or is not a PHP file: {$filePath}");
            }
        }

        if ($limitClasses) {
            $phpFiles = $phpFiles->filter(function ($filePath) use ($limitClasses) {
                $classesInFile = $this->extractClassesFromFile($filePath);
                return count(array_intersect($classesInFile, $limitClasses)) > 0;
            });

            if ($phpFiles->isEmpty()) {
                $this->warn('No PHP files found matching the specified classes. Aborting analysis.');
                Log::warning('AnalyzeFilesCommand: No .php files found matching the specified classes, aborting analysis.');

                return 0;
            }
        } else {
            if ($phpFiles->isEmpty()) {
                $this->warn('No PHP files found. Aborting analysis.');
                Log::warning('AnalyzeFilesCommand: No .php files found, aborting analysis.');

                return 0;
            }
        }

        $results = $this->processFiles($phpFiles, $dryRun);

        if ($outputFile) {
            // Run the analysis and export to JSON
            try {
                $this->analysisService->runAnalysis($dryRun, $outputFile);
                Log::info("AnalyzeFilesCommand: runAnalysis executed with dryRun={$dryRun}.");
            } catch (Throwable $e) {
                Log::error('AnalyzeFilesCommand: runAnalysis failed. Error: ' . $e->getMessage(), [
                    'exception' => $e,
                ]);
                $this->warn('runAnalysis failed: ' . $e->getMessage());

                return 1;
            }

            if ($outputFile) {
                $this->exportToJson($results->toArray(), $outputFile);
            }

            $this->info("Wrote analysis to [{$outputFile}].");
            Log::info("AnalyzeFilesCommand: Wrote analysis JSON to [{$outputFile}].");
        } else {
            // Queue ProcessAnalysisPassJob for each CodeAnalysis ID
            foreach ($results as $result) {
                ProcessAnalysisPassJob::dispatch($result['id'], $dryRun);
                Log::info("AnalyzeFilesCommand: Queued ProcessAnalysisPassJob for CodeAnalysis ID [{$result['id']}].");
            }

            $this->info("Queued [{$results->count()}] analysis jobs.");
            Log::info("AnalyzeFilesCommand: Queued [{$results->count()}] analysis jobs.");
        }

        $this->info("Done! Processed [{$results->count()}] file(s).");
        Log::info("AnalyzeFilesCommand: Completed. Processed [{$results->count()}] files.");

        return 0;
    }

    /**
     * Process each PHP file for analysis.
     *
     * @param  Collection<string>  $phpFiles  The collection of PHP file paths.
     * @param  bool  $dryRun  Indicates if the run is a dry run.
     * @return Collection<array> The collection of analysis results including 'id'.
     */
    protected function processFiles(Collection $phpFiles, bool $dryRun): Collection
    {
        $results = collect();
        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        foreach ($phpFiles as $filePath) {
            try {
                Log::info("AnalyzeFilesCommand: Analyzing [{$filePath}].");
                $analysisRecord = $this->analysisService->analyzeFile($filePath, $reparse = false);
                // Removed incorrect runAnalysis call here

                $results->push([
                    'id' => $analysisRecord->id,
                    'file' => $analysisRecord->file_path,
                    'analysis' => $analysisRecord->analysis,
                    'current_pass' => $analysisRecord->current_pass,
                    'completed_passes' => $analysisRecord->completed_passes,
                ]);
            } catch (Throwable $e) {
                Log::error("AnalyzeFilesCommand: Analysis failed [{$filePath}]: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
                $this->warn("Could not analyze [{$filePath}]: " . $e->getMessage());
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
        if (!$json) {
            $this->warn('Failed to encode JSON: ' . json_last_error_msg());
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

    /**
     * Extract class names from a PHP file.
     *
     * @param string $filePath
     * @return array
     */
    private function extractClassesFromFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        // Updated regex to handle namespaces and class modifiers
        preg_match_all('/(?:namespace\s+[\w\\\\]+;)?\s*(?:abstract\s+|final\s+)?class\s+(\w+)/', $content, $matches);
        return $matches[1] ?? [];
    }
}
