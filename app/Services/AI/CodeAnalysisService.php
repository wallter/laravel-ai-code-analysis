<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\OperationIdentifier;
use App\Jobs\ProcessAnalysisPassJob;
use App\Models\CodeAnalysis;
use App\Services\Parsing\ParserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
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
     * @param  string  $filePath  The path to the PHP file.
     * @param  bool  $reparse  Whether to force re-parsing the file.
     * @return CodeAnalysis The CodeAnalysis model instance.
     */
    public function analyzeFile(string $filePath, bool $reparse = false): CodeAnalysis
    {
        Log::debug("CodeAnalysisService: Checking or creating CodeAnalysis for [{$filePath}].");

        $analysis = CodeAnalysis::firstOrCreate(
            ['file_path' => $filePath],
            ['ast' => [], 'analysis' => [], 'current_pass' => 0, 'completed_passes' => []]
        );

        // Re-parse if no AST or if reparse is requested
        if ($reparse || empty($analysis->ast)) {
            Log::info("CodeAnalysisService: Parsing file [{$filePath}] into AST.");
            try {
                $ast = $this->parserService->parseFile($filePath);
                $analysis->ast = $ast;
                $analysis->analysis = $this->buildAstSummary($filePath, $ast);
                $analysis->save();
                Log::info("CodeAnalysisService: AST and summary updated for [{$filePath}].");
            } catch (Throwable $e) {
                Log::error("CodeAnalysisService: Failed to parse file [{$filePath}]. Error: {$e->getMessage()}");
                // Depending on requirements, you might want to rethrow or handle differently
            }
        }

        return $analysis;
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
        $classes = array_filter($parsedItems, fn ($item) => in_array($item['type'], [ParsedItemType::CLASS_TYPE->value, ParsedItemType::TRAIT_TYPE->value, ParsedItemType::INTERFACE_TYPE->value]));
        $functions = array_filter($parsedItems, fn ($item) => $item['type'] === ParsedItemType::FUNCTION_TYPE->value);

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
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code_analysis_id' => $analysis->id,
                'operation' => 'functionality',
                'score' => (float) $scoresData['functionality_score'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code_analysis_id' => $analysis->id,
                'operation' => 'style',
                'score' => (float) $scoresData['style_score'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code_analysis_id' => $analysis->id,
                'operation' => 'overall',
                'score' => (float) $scoresData['overall_score'],
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
     * Retrieve the configuration for the specified pass.
     *
     * @param  string  $passName  The name of the pass.
     * @return array|null The pass configuration or null if not found.
     */
    private function getPassConfig(string $passName): ?array
    {
        Log::debug("CodeAnalysisService: Retrieving configuration for pass '{$passName}'.");

        $passConfigs = config('ai.passes', []);
        $cfg = $passConfigs[$passName] ?? null;

        if (! $cfg) {
            Log::warning("CodeAnalysisService: No config found for pass [{$passName}]. Skipping.");
        }

        return $cfg;
    }
}
