<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\OperationIdentifier;
use App\Enums\ParsedItemType;
use App\Jobs\ProcessAnalysisPassJob;
use App\Models\AIResult;
use App\Models\CodeAnalysis;
use App\Services\AI\AIPromptBuilder;
use App\Services\AI\OpenAIService;
use App\Services\Parsing\ParserService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Throwable;
use Illuminate\Support\Str;

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
     * @param string $filePath The original file path (absolute or relative).
     * @return string The normalized relative file path.
     */
    protected function normalizeFilePath(string $filePath): string
    {
        $basePath = realpath(base_path());

        // Ensure both paths use forward slashes
        $filePath = str_replace(['\\'], '/', $filePath);
        $basePath = str_replace(['\\'], '/', $basePath);

        // Convert to absolute path if it's not already
        if (!Str::startsWith($filePath, ['/', 'http'])) {
            $filePath = realpath($basePath . '/' . ltrim($filePath, '/'));
        }

        // Ensure filePath is absolute
        if (!$filePath || !is_string($filePath)) {
            Log::error("CodeAnalysisService: Provided filePath '{$filePath}' could not be resolved to a real path.");
            return $filePath;
        }

        // Strip the base path from the file path to get the relative path
        if (Str::startsWith($filePath, $basePath)) {
            $relativePath = Str::replaceFirst($basePath . '/', '', $filePath);
            Log::debug("CodeAnalysisService: Normalized '{$filePath}' to '{$relativePath}'.");
            return $relativePath;
        }

        Log::warning("CodeAnalysisService: The file path '{$filePath}' does not start with base path '{$basePath}'. Storing as is.");
        return $filePath;
    }

    // ... (rest of the CodeAnalysisService remains unchanged)
}
