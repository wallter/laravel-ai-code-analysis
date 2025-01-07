<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\CodeAnalysis;
use App\Services\Parsing\ParserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Manages AST parsing and prepares CodeAnalysis records for AI processing.
 */
class CodeAnalysisService
{
    /**
     * Constructor to inject necessary services.
     *
     * @param  OpenAIService  $openAIService  Handles interactions with OpenAI API.
     * @param  ParserService  $parserService  Handles PHP file parsing.
     */
    public function __construct(
        protected OpenAIService $openAIService,
        protected ParserService $parserService
    ) {}

    /**
     * Analyze a PHP file by parsing it and creating/updating the CodeAnalysis record.
     *
     * @param  string  $filePath  The relative or absolute path to the PHP file.
     * @param  bool  $reparse  Whether to force re-parsing the file.
     * @return CodeAnalysis The CodeAnalysis model instance.
     */
    public function analyzeFile(string $filePath, bool $reparse = false): CodeAnalysis
    {
        Log::debug("CodeAnalysisService: Checking or creating CodeAnalysis for [{$filePath}].");

        // Normalize the file path to be relative
        $relativePath = $this->normalizeFilePath($filePath);

        $analysis = CodeAnalysis::firstOrCreate(
            ['file_path' => $relativePath],
            [
                'ast' => [],
                'analysis' => [],
                'current_pass' => 0,
                'completed_passes' => [],
                'relative_file_path' => $relativePath, // Ensure relative_file_path is set correctly
            ]
        );

        // Re-parse if no AST or if reparse is requested
        if ($reparse || empty($analysis->ast)) {
            Log::info("CodeAnalysisService: Parsing file [{$relativePath}] into AST.");
            try {
                $ast = $this->parserService->parseFile($relativePath);
                $analysis->ast = $ast;
                $analysis->analysis = $this->buildAstSummary($relativePath, $ast);
                $analysis->save();
                Log::info("CodeAnalysisService: AST and summary updated for [{$relativePath}].");
            } catch (Throwable $e) {
                Log::error("CodeAnalysisService: Failed to parse file [{$relativePath}]. Error: {$e->getMessage()}");
                // Depending on requirements, you might want to rethrow or handle differently
            }
        }

        return $analysis;
    }

    /**
     * Normalize the file path to ensure it is relative to the base path.
     *
     * @param  string  $filePath  The original file path (absolute or relative).
     * @return string The normalized relative file path.
     */
    protected function normalizeFilePath(string $filePath): string
    {
        $basePath = realpath(base_path());

        // Ensure both paths use forward slashes
        $filePath = str_replace(['\\'], '/', $filePath);
        $basePath = str_replace(['\\'], '/', $basePath);

        // Convert to absolute path if it's not already
        if (! Str::startsWith($filePath, ['/', 'http'])) {
            $filePath = realpath($basePath.'/'.ltrim($filePath, '/'));
        }

        // Ensure filePath is absolute
        if (! $filePath || ! is_string($filePath)) {
            Log::error("CodeAnalysisService: Provided filePath '{$filePath}' could not be resolved to a real path.");

            return $filePath;
        }

        // Strip the base path from the file path to get the relative path
        if (Str::startsWith($filePath, $basePath)) {
            $relativePath = Str::replaceFirst($basePath.'/', '', $filePath);
            Log::debug("CodeAnalysisService: Normalized '{$filePath}' to '{$relativePath}'.");

            return $relativePath;
        }

        Log::warning("CodeAnalysisService: The file path '{$filePath}' does not start with base path '{$basePath}'. Storing as is.");

        return $filePath;
    }

    /**
     * Collect PHP files from the given directory.
     *
     * @param  string  $directory  The directory to scan for PHP files.
     * @return Collection<string> The collection of PHP file paths.
     */
    public function collectPhpFiles(string $directory): Collection
    {
        Log::info("CodeAnalysisService: Collecting PHP files from directory '{$directory}'.");

        try {
            $phpFiles = collect(File::allFiles($directory))
                ->filter(fn($file) => $file->getExtension() === 'php')
                ->map(fn($file) => $this->normalizeFilePath($file->getPathname()));

            Log::debug('CodeAnalysisService: Collected '.$phpFiles->count()." PHP files from '{$directory}'.");

            return $phpFiles;
        } catch (Throwable $throwable) {
            Log::error("CodeAnalysisService: Failed to collect PHP files from directory '{$directory}'. Error: {$throwable->getMessage()}");

            return collect();
        }
    }

    /**
     * Build a summary of the AST for analysis.
     *
     * @param  string  $relativePath  The relative file path.
     * @param  array  $ast  The abstract syntax tree.
     * @return array The summary of the AST.
     */
    protected function buildAstSummary(string $relativePath, array $ast): array
    {
        // Implementation of AST summary building
        // This is a placeholder and should be implemented based on specific requirements
        return [
            'total_nodes' => count($ast),
            // Add more summary details as needed
        ];
    }

    /**
     * Run the complete analysis process.
     *
     * @param  bool    $dryRun      Whether to perform a dry run without saving results.
     * @param  string  $outputFile  The path to the output JSON file.
     * @return void
     */
    public function runAnalysis(bool $dryRun = false, string $outputFile = 'all.json'): void
    {
        Log::info("CodeAnalysisService: Starting analysis with dryRun={$dryRun}.");

        // Retrieve the folders from the parsing configuration
        $folders = config('parsing.folders', []);

        $results = [];

        foreach ($folders as $directory) {
            Log::info("CodeAnalysisService: Analyzing directory '{$directory}'.");
            $phpFiles = $this->collectPhpFiles($directory);

            foreach ($phpFiles as $filePath) {
                try {
                    Log::debug("CodeAnalysisService: Analyzing file '{$filePath}'.");
                    $analysis = $this->analyzeFile($filePath, $dryRun);

                    // Assuming you want to collect some data from each analysis
                    $results[] = [
                        'file_path' => $analysis->file_path,
                        'ast_summary' => $analysis->analysis,
                        'ai_output' => $analysis->ai_output,
                    ];
                } catch (Throwable $e) {
                    Log::error("CodeAnalysisService: Failed to analyze file '{$filePath}'. Error: {$e->getMessage()}");
                }
            }
        }

        if (!$dryRun) {
            try {
                File::put($outputFile, json_encode($results, JSON_PRETTY_PRINT));
                Log::info("CodeAnalysisService: Analysis results written to '{$outputFile}'.");
            } catch (Throwable $e) {
                Log::error("CodeAnalysisService: Failed to write analysis results to '{$outputFile}'. Error: {$e->getMessage()}");
            }
        } else {
            Log::info("CodeAnalysisService: Dry run completed. No results were saved.");
        }

        Log::info("CodeAnalysisService: Analysis process completed.");
    }
}
