<?php

namespace App\Console\Commands;

use App\Services\Parsing\ParserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Example AnalyzeCodeCommand
 * Extends BaseCodeCommand for shared options: --output-file, --limit-class, etc.
 */
class AnalyzeCodeCommand extends BaseCodeCommand
{
    protected $signature = 'analyze:code
        {--output-file= : Output .json file}
        {--limit-class= : Limit how many class-like items to process}
        {--limit-method= : Limit how many methods per class}';

    protected $description = 'Analyzes code by parsing files and optionally exporting JSON.';

    public function __construct(protected ParserService $parserService)
    {
        parent::__construct();
    }

    /**
     * The main logic, called by BaseCodeCommand->handle().
     */
    protected function executeCommand(): int
    {
        // Collect .php files from config
        $phpFiles = $this->parserService->collectPhpFiles();
        $outputFile = $this->getOutputFile();
        $limitClass = $this->getClassLimit();
        $limitMethod = $this->getMethodLimit();

        Log::info('AnalyzeCodeCommand starting.', [
            'file_count'   => $phpFiles->count(),
            'limit_class'  => $limitClass,
            'limit_method' => $limitMethod,
            'output_file'  => $outputFile,
        ]);

        $this->info(sprintf(
            "Found [%d] PHP files to analyze. limit-class=%d, limit-method=%d",
            $phpFiles->count(),
            $limitClass,
            $limitMethod
        ));

        // Optionally limit how many files we process
        if ($limitClass > 0 && $limitClass < $phpFiles->count()) {
            $phpFiles = $phpFiles->take($limitClass);
            $this->info("Applying limit-class: analyzing first {$limitClass} file(s).");
        }

        if ($phpFiles->isEmpty()) {
            $this->warn("No .php files found for analysis.");
            return 0;
        }

        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        // Collect overall analysis
        $allAnalysis = collect();

        foreach ($phpFiles as $filePath) {
            $this->info("Analyzing file: {$filePath}");

            // Resolve path without `normalizePath()`
            // Alternatively: `$fullPath = realpath($filePath) ?: $filePath;`
            $fullPath = $filePath;

            try {
                // Example usage: parse and analyze AST
                $analysisResults = $this->analyzeFile($fullPath, $limitMethod);

                $allAnalysis->push([
                    'file' => $fullPath,
                    'analysis' => $analysisResults,
                ]);

            } catch (\Throwable $e) {
                Log::error("Analysis failed for [{$fullPath}]: " . $e->getMessage());
                $this->warn("Could not analyze [{$fullPath}]: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Final output
        $this->info("Analysis complete. Processed {$allAnalysis->count()} file(s).");

        if ($outputFile) {
            $this->exportAnalysis($allAnalysis, $outputFile);
        }

        return 0;
    }

    /**
     * Illustrative example of analyzing a single file using ParserService & limitMethod.
     */
    protected function analyzeFile(string $filePath, int $limitMethod): array
    {
        // 1) Parse to get raw AST (or parseFileForItems if you have that method)
        $ast = $this->parserService->parseFile($filePath);
        if (empty($ast)) {
            return ['error' => 'Empty AST'];
        }

        // 2) Optionally do your unified visitor approach or direct analysis
        $items = $this->parserService->parseFileForItems($filePath, false);

        // 3) Apply method limit if needed
        $analysis = [];
        foreach ($items as $item) {
            if (in_array($item['type'], ['Class', 'Trait', 'Interface'], true)) {
                $methods = $item['details']['methods'] ?? [];
                if ($limitMethod > 0 && count($methods) > $limitMethod) {
                    $methods = array_slice($methods, 0, $limitMethod);
                }
                $analysis[] = [
                    'type'       => $item['type'],
                    'name'       => $item['name'],
                    'namespace'  => $item['namespace'] ?? '',
                    'methodCount'=> count($methods),
                ];
            } else {
                // type === 'Function', etc.
                $analysis[] = [
                    'type' => $item['type'],
                    'name' => $item['name'],
                ];
            }
        }

        return $analysis;
    }

    /**
     * Saves results to a .json file.
     */
    protected function exportAnalysis(Collection $allAnalysis, string $outputFile): void
    {
        $json = json_encode($allAnalysis->toArray(), JSON_PRETTY_PRINT);
        if (!$json) {
            $this->warn("Failed to encode analysis to JSON: " . json_last_error_msg());
            return;
        }
        @mkdir(dirname($outputFile), 0777, true);
        file_put_contents($outputFile, $json);
        $this->info("Analysis written to [{$outputFile}].");
    }
}