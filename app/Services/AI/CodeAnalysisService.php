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
     * @param  string  $filePath  The relative path to the PHP file.
     * @param  bool  $reparse  Whether to force re-parsing the file.
     * @return CodeAnalysis The CodeAnalysis model instance.
     */
    public function analyzeFile(string $filePath, bool $reparse = false): CodeAnalysis
    {
        Log::debug("CodeAnalysisService: Checking or creating CodeAnalysis for [{$filePath}].");

        // Normalize the relative file path
        $relativePath = $this->normalizeFilePath($filePath);

        $analysis = CodeAnalysis::firstOrCreate(
            ['file_path' => $relativePath],
            [
                'ast' => [],
                'analysis' => [],
                'current_pass' => 0,
                'completed_passes' => [],
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
     * Normalize the file path to ensure consistency.
     *
     * @param string $filePath The original file path.
     * @return string The normalized relative file path.
     */
    protected function normalizeFilePath(string $filePath): string
    {
        // Remove any leading slashes
        $normalizedPath = Str::startsWith($filePath, ['/', '\\']) ? ltrim($filePath, '/\\') : $filePath;

        // Replace backslashes with forward slashes for consistency
        $normalizedPath = str_replace(['\\'], '/', $normalizedPath);

        // Optionally, ensure it's relative to the base path
        $basePath = base_path();
        if (Str::startsWith($normalizedPath, $basePath)) {
            $normalizedPath = Str::after($normalizedPath, rtrim($basePath, '/') . '/');
        }

        return $normalizedPath;
    }

    /**
     * Summarize AST by scanning it with UnifiedAstVisitor.
     *
     * @param  string  $filePath  The path to the PHP file.
     * @param  array  $ast  The abstract syntax tree of the file.
     * @return array The summary of the AST.
     */
    protected function buildAstSummary(string $filePath, array $ast): array
    {
        Log::debug("CodeAnalysisService: Building AST summary for [{$filePath}].");

        // Initialize UnifiedAstVisitor
        $visitor = new UnifiedAstVisitor;
        $visitor->setCurrentFile($filePath);

        // Traverse AST with UnifiedAstVisitor
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        // Retrieve parsed items from the visitor
        $parsedItems = $visitor->getParsedItems();

        // Count different types of parsed items
        $classes = array_filter(
            $parsedItems,
            fn ($item) => in_array(
                $item['type'],
                [
                    ParsedItemType::CLASS_TYPE->value,
                    ParsedItemType::TRAIT_TYPE->value,
                    ParsedItemType::INTERFACE_TYPE->value
                ],
                true
            )
        );
        $functions = array_filter(
            $parsedItems,
            fn ($item) => $item['type'] === ParsedItemType::FUNCTION_TYPE->value
        );

        return [
            'class_count' => count($classes),
            'function_count' => count($functions),
            'items' => array_values($parsedItems),
        ];
    }

    /**
     * Collect all PHP files within a specified directory.
     *
     * @param  string  $directory  The directory to search within.
     * @return Collection<string> A collection of PHP file paths.
     */
    public function collectPhpFiles(string $directory = 'app'): Collection
    {
        Log::debug("CodeAnalysisService: Collecting PHP files from directory [{$directory}].");

        try {
            $files = $this->parserService->collectPhpFiles($directory);
            Log::info("CodeAnalysisService: Found [{$files->count()}] PHP file(s) in [{$directory}].");

            return $files;
        } catch (Throwable $throwable) {
            Log::error("CodeAnalysisService: Failed to collect PHP files from [{$directory}]. Error: {$throwable->getMessage()}");

            return collect();
        }
    }

    /**
     * Queue AI passes for the given CodeAnalysis instance.
     *
     * @param  CodeAnalysis  $analysis  The CodeAnalysis instance.
     * @param  bool  $dryRun  Whether to perform a dry run without saving results.
     */
    public function runAnalysis(CodeAnalysis $analysis, bool $dryRun = false): void
    {
        Log::info("CodeAnalysisService: Queueing multi-pass analysis for [{$analysis->file_path}].", [
            'dryRun' => $dryRun,
        ]);

        $completedPasses = (array) ($analysis->completed_passes ?? []);
        $passOrder = config('ai.operations.multi_pass_analysis.pass_order', []);

        foreach ($passOrder as $passName) {
            if (! in_array($passName, $completedPasses, true)) {
                $operationIdentifier = OperationIdentifier::tryFrom($passName);

                if (! $operationIdentifier) {
                    Log::error("CodeAnalysisService: Invalid pass name '{$passName}'. Cannot dispatch ProcessAnalysisPassJob.");

                    continue; // Skip this pass and continue with others
                }

                Log::info("CodeAnalysisService: Dispatching ProcessAnalysisPassJob for pass [{$passName}] => [{$analysis->file_path}].");
                ProcessAnalysisPassJob::dispatch(
                    codeAnalysisId: $analysis->id,
                    dryRun: $dryRun
                );
            }
        }
    }

    /**
     * Compute and store scores based on AI analysis results.
     *
     * @param  CodeAnalysis  $analysis  The CodeAnalysis instance.
     */
    public function computeAndStoreScores(CodeAnalysis $analysis): void
    {
        $latestScoringResult = $analysis->aiResults()
            ->where('pass_name', OperationIdentifier::SCORING_PASS->value)
            ->latest()
            ->first();

        if (! $latestScoringResult) {
            Log::warning("CodeAnalysisService: No scoring_pass result found for CodeAnalysis ID {$analysis->id}.");

            return;
        }

        $responseData = $latestScoringResult->response_text;

        try {
            $scoresData = json_decode($responseData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            Log::error("CodeAnalysisService: JSON decode error for CodeAnalysis ID {$analysis->id}: {$jsonException->getMessage()}");

            return;
        }

        $requiredFields = ['documentation_score', 'functionality_score', 'style_score', 'overall_score', 'summary'];
        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $scoresData)) {
                Log::error("CodeAnalysisService: Missing '{$field}' in AI response for CodeAnalysis ID {$analysis->id}.");

                return;
            }
        }

        $aiScores = [
            [
                'code_analysis_id' => $analysis->id,
                'operation' => 'documentation',
                'score' => (float) $scoresData['documentation_score'],
                'summary' => $scoresData['summary'] ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code_analysis_id' => $analysis->id,
                'operation' => 'functionality',
                'score' => (float) $scoresData['functionality_score'],
                'summary' => $scoresData['summary'] ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code_analysis_id' => $analysis->id,
                'operation' => 'style',
                'score' => (float) $scoresData['style_score'],
                'summary' => $scoresData['summary'] ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code_analysis_id' => $analysis->id,
                'operation' => 'overall',
                'score' => (float) $scoresData['overall_score'],
                'summary' => $scoresData['summary'] ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $analysis->aiScores()->insert($aiScores);

        Log::info("CodeAnalysisService: Scores computed for [{$analysis->file_path}].", $scoresData);
    }

    /**
     * Handle the scoring pass.
     *
     * @param  CodeAnalysis  $analysis  The CodeAnalysis instance.
     * @param  string  $passName  The name of the completed pass.
     */
    protected function handleScoringPass(CodeAnalysis $analysis, string $passName): void
    {
        // Define the scoring pass based on passName if different passes require different scoring logic
        // For this example, we assume a general scoring pass
        $scoringPassName = OperationIdentifier::SCORING_PASS->value;
        $scoringPassConfig = $this->getPassConfig($scoringPassName);

        if (! $scoringPassConfig) {
            Log::warning("CodeAnalysisService: No configuration found for scoring pass '{$scoringPassName}'. Skipping scoring.");

            return;
        }

        Log::info("CodeAnalysisService: Executing scoring pass '{$scoringPassName}'.");

        // Assuming scoring pass has been dispatched and completed, compute and store scores
        $this->computeAndStoreScores($analysis);
        Log::info("CodeAnalysisService: Scoring pass '{$scoringPassName}' completed.");
    }

    /**
     * Retrieve previous analysis results.
     *
     * @param  CodeAnalysis  $analysis  The CodeAnalysis instance.
     * @return string Consolidated previous analysis results.
     */
    protected function getPreviousResults(CodeAnalysis $analysis): string
    {
        return $analysis->aiResults()
            ->whereIn('operation_identifier', OperationIdentifier::cases())
            ->orderBy('id', 'asc')
            ->pluck('response_text')
            ->implode("\n\n---\n\n");
    }

    /**
     * Execute the AI operation for a given pass.
     *
     * @param  CodeAnalysis  $analysis  The CodeAnalysis instance.
     * @param  string  $passName  The name of the pass.
     * @param  array  $passConfig  The configuration for the pass.
     * @return array The prompt and response data.
     */
    private function executeAiOperation(CodeAnalysis $analysis, string $passName, array $passConfig): array
    {
        // Initialize AiPromptBuilder with ENUM
        try {
            $operationIdentifier = OperationIdentifier::from($passConfig['operation_identifier']);
        } catch (\ValueError $valueError) {
            Log::error("CodeAnalysisService: Invalid OperationIdentifier '{$passConfig['operation_identifier']}' for pass '{$passName}'. Skipping.");
            throw $valueError;
        }

        $promptBuilder = new AIPromptBuilder(
            operationIdentifier: $operationIdentifier,
            config: $passConfig,
            astData: $analysis->ast ?? [],
            rawCode: $this->getRawCode($analysis->file_path),
            previousResults: $this->getPreviousResults($analysis)
        );

        // Build prompt
        Log::debug("AnalysisPassService: Building prompt for pass '{$passName}'.");
        $prompt = $promptBuilder->buildPrompt();
        Log::debug("AnalysisPassService: Prompt built for pass '{$passName}': {$prompt}");

        // Determine the correct token parameter based on model configuration
        $modelName = $passConfig['model'] ?? config('ai.default.model');
        $modelConfig = config("ai.models.{$modelName}", []);
        $tokenLimitParam = $modelConfig['token_limit_parameter'] ?? 'max_tokens';

        // Prepare AI operation parameters
        $aiParams = [
            'model' => $modelConfig['model_name'] ?? $modelName,
            'temperature' => $passConfig['temperature'] ?? config('ai.default.temperature'),
            'messages' => json_decode($prompt, true),
        ];

        // Set the correct token limit parameter
        if ($tokenLimitParam === 'max_completion_tokens') {
            $aiParams['max_completion_tokens'] = $passConfig[$tokenLimitParam] ?? $modelConfig['max_tokens'] ?? config('ai.default.max_tokens');
        } else {
            $aiParams['max_tokens'] = $passConfig[$tokenLimitParam] ?? $modelConfig['max_tokens'] ?? config('ai.default.max_tokens');
        }

        // Log the parameters being sent (excluding sensitive information)
        Log::debug("AnalysisPassService: Performing AI operation for pass '{$passName}' with parameters: ".json_encode([
            'model' => $aiParams['model'],
            $tokenLimitParam => $aiParams[$tokenLimitParam],
            'temperature' => $aiParams['temperature'],
            'messages' => '<<omitted>>', // To avoid logging sensitive data
        ]));

        // Perform AI operation
        Log::debug("AnalysisPassService: Performing AI operation for pass '{$passName}'.");
        $responseData = $this->openAIService->performOperation(
            operationIdentifier: $operationIdentifier,
            params: $aiParams
        );
        Log::debug("AnalysisPassService: AI operation response for pass '{$passName}': ".json_encode($responseData));

        return [
            'prompt' => $prompt,
            'response_data' => $responseData,
        ];
    }

    /**
     * Extract usage metrics from the AI response.
     *
     * @return array The extracted usage metrics.
     */
    private function extractUsageMetrics(): array
    {
        Log::debug('AnalysisPassService: Extracting usage metrics.');
        $usage = $this->openAIService->getLastUsage();
        $metadata = [];

        if (! empty($usage)) {
            Log::debug('AnalysisPassService: Usage metrics found: '.json_encode($usage));
            $metadata['usage'] = $usage;
            // Compute cost
            $COST_PER_1K_TOKENS = env('OPENAI_COST_PER_1K_TOKENS', 0.002); // e.g., $0.002 per 1k tokens
            $totalTokens = $usage['total_tokens'] ?? 0;
            $metadata['cost_estimate_usd'] = round(($totalTokens / 1000) * $COST_PER_1K_TOKENS, 6);
            Log::debug("AnalysisPassService: Cost estimate based on tokens: {$metadata['cost_estimate_usd']} USD.");
        } else {
            Log::warning('AnalysisPassService: No usage metrics found.');
        }

        return $metadata;
    }

    /**
     * Create an AIResult entry in the database.
     *
     * @param  CodeAnalysis  $analysis  The CodeAnalysis instance.
     * @param  string  $passName  The name of the pass.
     * @param  string  $prompt  The prompt sent to the AI.
     * @param  string  $responseData  The response received from the AI.
     * @param  array  $metadata  Additional metadata like usage metrics.
     */
    private function createAiResultEntry(CodeAnalysis $analysis, string $passName, string $prompt, string $responseData, array $metadata): void
    {
        Log::debug("AnalysisPassService: Creating AIResult entry for pass '{$passName}'.");

        try {
            AIResult::create([
                'code_analysis_id' => $analysis->id,
                'pass_name' => $passName,
                'prompt_text' => $prompt,
                'response_text' => $responseData,
                'metadata' => $metadata,
            ]);

            Log::info("AnalysisPassService: AIResult entry created for pass '{$passName}'.");
        } catch (Throwable $exception) {
            Log::error("AnalysisPassService: Exception while creating AIResult for pass '{$passName}': {$exception->getMessage()}", [
                'exception' => $exception,
            ]);
            throw $exception;
        }
    }

    /**
     * Retrieve the raw code from the file path.
     *
     * @param  string  $filePath  The relative path to the PHP file.
     * @return string The raw code content.
     */
    private function getRawCode(string $filePath): string
    {
        // Use the normalized file path
        $relativePath = $this->normalizeFilePath($filePath);
        // Construct the absolute path using base_path
        $absolutePath = base_path($relativePath);

        if (File::exists($absolutePath)) {
            Log::debug("AnalysisPassService: Retrieving raw code from '{$absolutePath}'.");

            return File::get($absolutePath);
        }

        Log::warning("AnalysisPassService: Raw code file '{$absolutePath}' does not exist.");

        return '';
    }

    /**
     * Retrieve the pass configuration.
     *
     * @param  string  $passName  The name of the pass.
     * @return array|null The configuration array or null if not found.
     */
    protected function getPassConfig(string $passName): ?array
    {
        return config("ai.operations.multi_pass_analysis.passes.{$passName}");
    }
}
