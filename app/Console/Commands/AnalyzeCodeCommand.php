<?php

namespace App\Console\Commands;

use App\Models\CodeAnalysis;
use App\Models\ParsedItem;
use App\Services\Parsing\ParserService;
use App\Services\AI\CodeAnalysisService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
        $parsedItems = ParsedItem::all();

        if ($parsedItems->isEmpty()) {
            $this->info("No parsed items found for analysis.");
            return 0;
        }

        if ($this->isVerbose()) {
            $this->info("Analyzing {$parsedItems->count()} parsed items.");
        }

        $bar = $this->output->createProgressBar($parsedItems->count());
        $bar->start();

        foreach ($parsedItems as $item) {
            if ($this->isVerbose()) {
                $this->info("Analyzing item: {$item->name}");
            }

            try {
                // Perform AI analysis using CodeAnalysisService
                $analysis = $this->codeAnalysisService->analyzeAst($item->file_path, $this->getMethodLimit());

                // Store the analysis result using the relationship
                $item->codeAnalysis()->updateOrCreate([], [
                    'file_path'        => $item->file_path,
                    'ast'              => $analysis['ast_data'] ?? [],
                    'analysis'         => $analysis['ai_results'] ?? [],
                    'ai_output'        => null, // Initialize or set as needed
                    'current_pass'     => 0,    // Initialize or set as needed
                    'completed_passes' => [],   // Initialize or set as needed
                ]);

                if ($this->isVerbose()) {
                    $this->info("Analysis completed for: {$item->name}");
                }
            } catch (\Exception $e) {
                $this->warn("Failed to analyze {$item->name}: {$e->getMessage()}");
                if ($this->isVerbose()) {
                    $this->error("Error details: " . $e->getTraceAsString());
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Export AI analysis results to JSON if requested
        $outputFile = $this->getOutputFile();
        if ($outputFile) {
            if ($this->isVerbose()) {
                $this->info("Exporting AI analysis results to JSON file: {$outputFile}");
            }
            $codeAnalyses = CodeAnalysis::with('parsedItem')->get()->toArray();
            $this->exportJson($codeAnalyses, $outputFile);
        }

        $this->info("Code analysis results saved to [{$outputFile}]");
        $this->info("Analysis complete.");
        return 0;
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
