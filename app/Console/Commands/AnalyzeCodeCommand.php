<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\CodeAnalysis;
use App\Services\Parsing\ParserService;
use App\Services\AI\CodeAnalysisService;
use Illuminate\Support\Collection;

/**
 * The AnalyzeCodeCommand extends a shared BaseCodeCommand
 * providing common CLI options and utility methods:
 *   --output-file
 *   --limit-class
 *   --limit-method
 *
 * This command:
 * 1) Collects .php files from ParserService
 * 2) Optionally limits the number of files (limit-class)
 * 3) For each file, extracts individual functions and calls CodeAnalysisService->analyzeAst(...)
 * 4) Stores results in 'code_analyses' DB table
 * 5) Optionally writes results to a JSON file
 */
class AnalyzeCodeCommand extends BaseCodeCommand
{
    /**
     * Overriding the signature to add or rename options if needed.
     */
    protected $signature = 'code:analyze
        {--output-file= : Write JSON output to this file (ensures .json if missing).}
        {--limit-class= : Limit how many .php files we analyze in total.}
        {--limit-method= : Limit how many methods per class to process.}';

    protected $description = 'Analyze PHP files, generate AST, and apply multi-pass AI analysis.';

    /**
     * @var ParserService
     */
    protected ParserService $parserService;

    /**
     * @var CodeAnalysisService
     */
    protected CodeAnalysisService $codeAnalysisService;

    public function __construct(
        ParserService $parserService,
        CodeAnalysisService $codeAnalysisService
    ) {
        parent::__construct();
        $this->parserService       = $parserService;
        $this->codeAnalysisService = $codeAnalysisService;
    }

    /**
     * The logic for this command, called by BaseCodeCommand::handle().
     * If any unhandled exception occurs, we log & return non-zero.
     */
    protected function executeCommand(): int
    {
        $totalStartTime = microtime(true);
        try {
            // Collect .php files from config
            $phpFiles = $this->parserService->collectPhpFiles()->unique();

            // Pull shared options from BaseCodeCommand
            $outputFile  = $this->getOutputFile();
            $limitClass  = $this->getClassLimit();  // Interpreted as "max number of files"
            $limitMethod = $this->getMethodLimit(); // For each file, how many methods to analyze

            // Logging initial state
            Log::info('AnalyzeCodeCommand started.', [
                'output_file'  => $outputFile,
                'limit_class'  => $limitClass,
                'limit_method' => $limitMethod,
                'file_count'   => $phpFiles->count(),
            ]);

            $this->info(sprintf(
                "Found [%d] total PHP files. limit-class=%d, limit-method=%d",
                $phpFiles->count(),
                $limitClass,
                $limitMethod
            ));

            // If limitClass is set, reduce the set of files to that many
            if ($limitClass > 0 && $limitClass < $phpFiles->count()) {
                $phpFiles = $phpFiles->take($limitClass);
                $this->info("Applying limit-class: analyzing only first {$limitClass} file(s).");
                Log::info("Applying limit-class: analyzing only first {$limitClass} file(s).");
            }

            if ($phpFiles->isEmpty()) {
                $this->warn('No .php files found (or all limited out). Nothing to analyze.');
                Log::warning('No .php files found (or all limited out). Nothing to analyze.');
                return 0; // Normal exit
            }

            // Setup progress bar
            $fileCount = $phpFiles->count();
            $progressBar = $this->output->createProgressBar($fileCount);
            $progressBar->start();

            // We store final results for optional output
            $analysisResults = collect();

            // Wrap DB changes in a transaction
            DB::beginTransaction();

            foreach ($phpFiles as $filePath) {
                // Advance progress
                $progressBar->advance();

                if ($this->output->isVerbose()) {
                    $this->line("Processing file: [{$filePath}]");
                }

                Log::info("Starting analysis of file: {$filePath}");

                try {
                    // Extract individual functions from the file
                    $functions = $this->parserService->getFunctionsFromFile($filePath);

                    if ($limitMethod > 0 && $limitMethod < count($functions)) {
                        $functions = array_slice($functions, 0, $limitMethod);
                        $this->info("Applying limit-method: analyzing only first {$limitMethod} method(s) in {$filePath}.");
                        Log::info("Applying limit-method: analyzing only first {$limitMethod} method(s) in {$filePath}.");
                    }

                    foreach ($functions as $function) {
                        $this->info("Analyzing function: [{$function['name']}] in [{$filePath}]");
                        Log::info("Starting analysis of function: {$function['name']} in {$filePath}");

                        $fileStartTime = microtime(true);

                        try {
                            // Analyze individual function
                            $analysisData = $this->codeAnalysisService->analyzeAst($function['ast'], $limitMethod);

                            CodeAnalysis::updateOrCreate(
                                ['file_path' => $this->parserService->normalizePath($filePath), 'function_name' => $function['name']],
                                [
                                    // We store the entire analysis as well as AST in DB
                                    'ast'      => json_encode($function['ast'], JSON_UNESCAPED_SLASHES),
                                    'analysis' => json_encode($analysisData, JSON_UNESCAPED_SLASHES),
                                ]
                            );

                            if ($outputFile) {
                                $analysisResults->put("{$filePath}::{$function['name']}", $analysisData);
                            }

                            $fileEndTime = microtime(true);
                            $duration = round($fileEndTime - $fileStartTime, 2);

                            Log::info("Function analyzed successfully: {$function['name']} in {$filePath}", [
                                'duration_seconds' => $duration,
                            ]);
                        } catch (\Throwable $e) {
                            Log::error("Analysis failed for function {$function['name']} in {$filePath}", [
                                'exception' => $e,
                            ]);
                            $this->error("Analysis error on function [{$function['name']}] in [{$filePath}]. See logs for details.");
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to extract functions from {$filePath}", [
                        'exception' => $e,
                    ]);
                    $this->error("Failed to process [{$filePath}]. See logs for details.");
                }
            }

            $progressBar->finish();
            $this->line(''); // New line after progress bar
            DB::commit();

            // If requested, write analysis results to JSON
            if ($outputFile) {
                $this->exportResults($outputFile, $analysisResults->toArray());
            }

            $totalEndTime = microtime(true);
            $totalDuration = round($totalEndTime - $totalStartTime, 2);
            Log::info('Code analysis completed successfully.', [
                'total_duration_seconds' => $totalDuration,
                'total_files_analyzed'   => $fileCount,
            ]);
            $this->info('Code analysis completed successfully.');
            $this->info("Total time taken: {$totalDuration} seconds.");
            return 0;
        } catch (\Throwable $e) {
            Log::error('AnalyzeCodeCommand failed.', ['exception' => $e]);
            $this->error('An error occurred during code analysis. See logs for details.');
            return 1;
        }
    }

    /**
     * Output to JSON file if requested.
     */
    protected function exportResults(string $filePath, array $data): void
    {
        try {
            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($jsonData === false) {
                throw new \RuntimeException('Failed to JSON-encode analysis results.');
            }
            $dir = dirname($filePath);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0775, true);
            }
            // Ensure the file has a .json extension
            if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'json') {
                $filePath .= '.json';
            }
            File::put($filePath, $jsonData);
            $this->info("Analysis results saved to [{$filePath}]");
            Log::info("Analysis results exported to [{$filePath}]");
        } catch (\Throwable $e) {
            Log::error("Could not write results to [{$filePath}]: " . $e->getMessage());
            $this->error("Export to {$filePath} failed. Check logs for details.");
        }
    }
}
