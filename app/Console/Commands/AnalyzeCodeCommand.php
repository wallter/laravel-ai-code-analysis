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
 * AnalyzeCodeCommand extends the shared BaseCodeCommand, providing:
 *  --output-file   => JSON output destination
 *  --limit-class   => max # of files to analyze
 *  --limit-method  => max # of methods per class
 *
 * 1) Collects .php files from ParserService
 * 2) Optionally limits how many files we analyze
 * 3) Parses each file's AST and calls CodeAnalysisService->analyzeAst(...)
 * 4) Stores results in 'code_analyses' table
 * 5) Optionally writes results to JSON
 */
class AnalyzeCodeCommand extends BaseCodeCommand
{
    protected $signature = 'code:analyze
        {--output-file= : Write JSON output to this file (appends .json if missing).}
        {--limit-class= : Limit how many .php files we analyze.}
        {--limit-method= : Limit how many methods per class get processed.}';

    protected $description = 'Analyze PHP files, gather AST, and apply multi-pass AI analysis.';

    public function __construct(
        protected ParserService      $parserService,
        protected CodeAnalysisService $codeAnalysisService
    ) {
        parent::__construct();
    }

    /**
     * Called by BaseCodeCommand::handle().
     */
    protected function executeCommand(): int
    {
        $startTime = microtime(true);

        try {
            $phpFiles   = $this->parserService->collectPhpFiles()->unique();
            $outputFile = $this->getOutputFile();
            $limitClass = $this->getClassLimit();
            $limitMethod= $this->getMethodLimit();

            Log::info('AnalyzeCodeCommand starting.', [
                'file_count'   => $phpFiles->count(),
                'limit_class'  => $limitClass,
                'limit_method' => $limitMethod,
                'output_file'  => $outputFile,
            ]);

            $this->info(sprintf(
                "Discovered [%d] PHP files. limit-class=%d, limit-method=%d",
                $phpFiles->count(),
                $limitClass,
                $limitMethod
            ));

            if ($limitClass > 0 && $limitClass < $phpFiles->count()) {
                $phpFiles = $phpFiles->take($limitClass);
                $this->info("Applying limit-class: analyzing only the first {$limitClass} file(s).");
                Log::debug("limit-class in effect => truncated to {$limitClass} file(s).");
            }
            if ($phpFiles->isEmpty()) {
                $this->warn('No .php files to analyze after applying limit-class.');
                return 0;
            }

            $analysisResults = collect();
            DB::beginTransaction();

            // Setup progress bar
            $bar = $this->output->createProgressBar($phpFiles->count());
            $bar->start();

            foreach ($phpFiles as $filePath) {
                $bar->advance();
                $this->lineIfVerbose("Analyzing file: [{$filePath}]");
                Log::info("Analyzing file: {$filePath}");

                try {
                    // The main multi-pass analysis
                    $analysisData = $this->codeAnalysisService->analyzeAst($filePath, $limitMethod);

                    // If the analysis came back empty or partial, warn
                    if (empty($analysisData)) {
                        $this->warn("No analysis data returned for [{$filePath}].");
                        Log::warning("No analysis data produced for {$filePath}.");
                    }

                    // Persist in code_analyses DB table
                    CodeAnalysis::updateOrCreate(
                        ['file_path' => $this->parserService->normalizePath($filePath)],
                        ['ast' => json_encode($analysisData['ast_data'] ?? [], JSON_UNESCAPED_SLASHES),
                         'ai_output' => json_encode($analysisData['ai_results'] ?? [], JSON_UNESCAPED_SLASHES),
                        ]
                    );

                    // For optional final JSON output
                    if ($outputFile) {
                        $analysisResults->put($filePath, $analysisData);
                    }

                    Log::info("File [{$filePath}] analyzed successfully.");
                } catch (\Throwable $e) {
                    Log::error("Analysis failed for [{$filePath}]: {$e->getMessage()}", [
                        'exception' => $e,
                    ]);
                    $this->error("Error analyzing file [{$filePath}]. Check logs for more info.");
                }
            }

            $bar->finish();
            $this->newLine();
            DB::commit();

            if ($outputFile) {
                $this->exportResults($outputFile, $analysisResults->toArray());
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Analysis complete. Time: {$duration}s");
            Log::info("AnalyzeCodeCommand completed successfully.", ['duration' => $duration]);
            return 0;
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("AnalyzeCodeCommand encountered an error.", ['exception' => $th]);
            $this->error("A fatal error occurred. Check logs for details.");
            return 1;
        }
    }

    /**
     * Logs a line only if verbose mode is enabled.
     */
    protected function lineIfVerbose(string $message): void
    {
        if ($this->output->isVerbose()) {
            $this->line($message);
        }
    }

    /**
     * Export the final analysis results to JSON.
     */
    protected function exportResults(string $filePath, array $data): void
    {
        try {
            // Ensure .json extension
            if (!str_ends_with(strtolower($filePath), '.json')) {
                $filePath .= '.json';
            }

            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($jsonData === false) {
                throw new \RuntimeException('Failed to JSON encode analysis results.');
            }

            $dir = dirname($filePath);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0775, true, true);
            }

            File::put($filePath, $jsonData);

            $this->info("Analysis results saved to [{$filePath}]");
            Log::debug("Analysis results exported to [{$filePath}]", [
                'analysisResultsCount' => count($data),
            ]);
        } catch (\Throwable $e) {
            Log::error("Could not export results to [{$filePath}]: " . $e->getMessage());
            $this->error("Failed to export JSON to [{$filePath}]. See logs for details.");
        }
    }
}
