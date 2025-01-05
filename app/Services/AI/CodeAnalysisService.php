<?php

namespace App\Services\AI;

use App\Jobs\ProcessAnalysisPassJob;
use App\Models\AIScore;
use App\Models\CodeAnalysis;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\UnifiedAstVisitor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

/**
 * Manages AST parsing and multi-pass AI for code analysis in an asynchronous manner.
 */
class CodeAnalysisService
{
    /**
     * Initialize the CodeAnalysisService with necessary dependencies.
     *
     * @param  OpenAIService  $openAIService  The service handling OpenAI interactions.
     * @param  ParserService  $parserService  The service handling PHP file parsing.
     */
    public function __construct(
        protected OpenAIService $openAIService,
        protected ParserService $parserService
    ) {}

    /**
     * Get the ParserService instance.
     *
     * @return ParserService The parser service.
     */
    public function getParserService(): ParserService
    {
        return $this->parserService;
    }

    /**
     * After all passes are dispatched, ensure scoring is processed.
     *
     * @param  CodeAnalysis  $analysis
     * @return void
     */
    protected function ensureScoringProcessed(CodeAnalysis $analysis): void
    {
        if ($analysis->completed_passes && in_array('scoring_pass', $analysis->completed_passes, true)) {
            $this->computeAndStoreScores($analysis);
        }
    }

    /**
     * Create or reuse a CodeAnalysis for the specified file.
     * Optionally re-parse if $reparse is true.
     *
     * @param  string  $filePath  The path to the PHP file.
     * @param  bool  $reparse  Whether to force re-parsing the file.
     * @return CodeAnalysis The CodeAnalysis model instance.
     */
    public function analyzeFile(string $filePath, bool $reparse = false): CodeAnalysis
    {
        Log::debug("CodeAnalysisService: Checking or creating CodeAnalysis for {$filePath}.");
        $analysis = CodeAnalysis::firstOrCreate(
            ['file_path' => $filePath],
            ['ast' => [], 'analysis' => [], 'current_pass' => 0, 'completed_passes' => []]
        );

        // Re-parse if no AST or if user explicitly wants a fresh parse
        if ($reparse || empty($analysis->ast)) {
            Log::info("CodeAnalysisService: Parsing file [{$filePath}] into AST.");
            $ast = $this->parserService->parseFile($filePath);
            $analysis->ast = $ast;
            $analysis->analysis = $this->buildAstSummary($filePath, $ast);
            $analysis->save();
        }

        if ($passType === 'scoring') {
            $prompt .= "\n\nPRIOR ANALYSIS RESULTS:\n";
            $previousTexts = $analysis->aiResults()
                ->whereIn('pass_name', ['doc_generation', 'functional_analysis', 'style_convention'])
                ->orderBy('id', 'asc')
                ->pluck('response_text')
                ->implode("\n\n---\n\n");
            $prompt .= $previousTexts;
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
        $visitor = new UnifiedAstVisitor;
        $visitor->setCurrentFile($filePath);

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $items = $visitor->getItems();
        $classes = array_filter($items, fn ($i) => in_array($i['type'], ['Class', 'Trait', 'Interface']));
        $functions = array_filter($items, fn ($i) => $i['type'] === 'Function');

        return [
            'class_count' => count($classes),
            'function_count' => count($functions),
            'items' => array_values($items),
        ];
    }

    /**
     * Instead of synchronous calls, we now queue each missing pass.
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
                // Dispatch a job for each missing pass
                Log::info("Dispatching ProcessAnalysisPassJob for pass [{$passName}] => [{$analysis->file_path}].");
                ProcessAnalysisPassJob::dispatch(
                    codeAnalysisId: $analysis->id,
                    passName: $passName,
                    dryRun: $dryRun
                );
            }
        }
    }

    /**
     * Build a prompt for a specific pass (used by ProcessAnalysisPassJob).
     *
     * @param  CodeAnalysis  $analysis  The CodeAnalysis instance.
     * @param  string  $passName  The name of the pass.
     * @return string The constructed prompt.
     */
    public function buildPromptForPass(CodeAnalysis $analysis, string $passName): string
    {
        // 1) Get the pass config
        $allPassConfigs = config('ai.operations.multi_pass_analysis', []);
        $cfg = $allPassConfigs[$passName] ?? null;

        $passType = $cfg['type'] ?? 'both';
        $base = $cfg['prompt'] ?? 'Analyze the following code:';

        $ast = $analysis->ast ?? [];
        $rawCode = $this->getRawCode($analysis->file_path);

        $prompt = $base;

        // For normal pass types
        if ($passType === 'ast' || $passType === 'both') {
            $prompt .= "\n\nAST Data:\n".json_encode($ast, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if ($passType === 'raw' || $passType === 'both') {
            $prompt .= "\n\nRaw Code:\n".$rawCode;
        }

        // If passType is "previous", gather prior pass outputs
        if ($passType === 'previous') {
            $prompt .= "\n\nPRIOR ANALYSIS RESULTS:\n";
            $previousTexts = $analysis->aiResults()
                ->orderBy('id', 'asc')
                ->pluck('response_text')
                ->implode("\n\n---\n\n");
            $prompt .= $previousTexts;
        }

        return $prompt . "\n\nRespond with structured insights.\n";
    }

    /**
     * Get the raw code from the specified file path.
     *
     * @param  string  $filePath  The path to the PHP file.
     * @return string The raw PHP code.
     */
    protected function getRawCode(string $filePath): string
    {
        try {
            return File::get($filePath);
        } catch (\Exception $exception) {
            Log::warning("Could not read file [{$filePath}]: ".$exception->getMessage());

            return '';
        }
    }

    /*
     * Compute meaningful scores based on AI analysis results and store them.
     *
     * @param  CodeAnalysis  $analysis
     * @return void
     */
    public function computeAndStoreScores(CodeAnalysis $analysis): void
    {
        $scores = [
            'documentation_score' => 0,
            'functionality_score' => 0,
            'style_score' => 0,
            'overall_score' => 0,
        ];

        $aiResults = $analysis->aiResults()
            ->whereIn('pass_name', ['doc_generation', 'functional_analysis', 'style_convention'])
            ->get();

        foreach ($aiResults as $result) {
            if ($result->pass_name === 'doc_generation') {
                $scores['documentation_score'] = $this->extractScore($result->response_text, 'Documentation Score');
            }
            if ($result->pass_name === 'functional_analysis') {
                $scores['functionality_score'] = $this->extractScore($result->response_text, 'Functionality Score');
            }
            if ($result->pass_name === 'style_convention') {
                $scores['style_score'] = $this->extractScore($result->response_text, 'Style Score');
            }
        }

        $scores['overall_score'] = round((
            $scores['documentation_score'] +
            $scores['functionality_score'] +
            $scores['style_score']
        ) / 3, 2);

        $operations = [
            'documentation' => 'documentation_score',
            'functionality' => 'functionality_score',
            'style' => 'style_score',
            'overall' => 'overall_score',
        ];

        $aiScores = [];
        foreach ($operations as $operation => $key) {
            $aiScores[] = [
                'code_analysis_id' => $analysis->id,
                'operation' => $operation,
                'score' => $scores[$key],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        AIScore::insert($aiScores);

        Log::info("CodeAnalysisService: Scores computed for [{$analysis->file_path}].", $scores);
    }

    /**
     * Extract a specific score from AI response text.
     *
     * @param  string  $responseText
     * @param  string  $scoreLabel
     * @return float
     */
    protected function extractScore(string $responseText, string $scoreLabel): float
    {
        if (preg_match("/{$scoreLabel}:\s*(\d+(\.\d+)?)/i", $responseText, $matches)) {
            return (float) $matches[1];
        }

        Log::warning("extractScore: Unable to find '{$scoreLabel}' in response.");
        return 0.0;
    }
}
