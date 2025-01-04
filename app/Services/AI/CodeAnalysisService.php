<?php

namespace App\Services\AI;

use App\Models\CodeAnalysis;
use App\Models\AIResult;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\UnifiedAstVisitor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use App\Jobs\ProcessAnalysisPassJob;

/**
 * Manages AST parsing + multi-pass AI for code analysis in an async manner.
 */
class CodeAnalysisService
{
    public function __construct(
        protected OpenAIService  $openAIService,
        protected ParserService  $parserService
    ) {
    }

    public function getParserService(): ParserService
    {
        return $this->parserService;
    }

    /**
     * Create or reuse a CodeAnalysis for the specified file.
     * Optionally re-parse if $reparse is true.
     */
    public function analyzeFile(string $filePath, bool $reparse = false): CodeAnalysis
    {
        Log::debug("CodeAnalysisService: Checking or creating CodeAnalysis for {$filePath}.");
        $analysis = CodeAnalysis::firstOrCreate(
            ['file_path' => $filePath],
            ['ast' => [], 'analysis' => [], 'current_pass' => 0, 'completed_passes' => []]
        );

        // Re-parse if no AST or if user explicitly wants fresh parse
        if ($reparse || empty($analysis->ast)) {
            Log::info("CodeAnalysisService: Parsing file [{$filePath}] into AST.");
            $ast = $this->parserService->parseFile($filePath);
            $analysis->ast      = $ast;
            $analysis->analysis = $this->buildAstSummary($filePath, $ast);
            $analysis->save();
        }

        return $analysis;
    }

    /**
     * Summarize AST by scanning it with UnifiedAstVisitor.
     */
    protected function buildAstSummary(string $filePath, array $ast): array
    {
        Log::debug("CodeAnalysisService: Building AST summary for [{$filePath}].");
        $visitor = new UnifiedAstVisitor();
        $visitor->setCurrentFile($filePath);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $items     = $visitor->getItems();
        $classes   = array_filter($items, fn($i) => in_array($i['type'], ['Class','Trait','Interface']));
        $functions = array_filter($items, fn($i) => $i['type'] === 'Function');

        return [
            'class_count'    => count($classes),
            'function_count' => count($functions),
            'items'          => array_values($items),
        ];
    }

    /**
     * Instead of synchronous calls, we now queue each missing pass.
     */
    public function runAnalysis(CodeAnalysis $analysis, bool $dryRun = false): void
    {
        Log::info("CodeAnalysisService: Queueing multi-pass analysis for [{$analysis->file_path}].", [
            'dryRun' => $dryRun
        ]);

        $completedPasses = (array) ($analysis->completed_passes ?? []);
        $passOrder       = config('ai.operations.multi_pass_analysis.pass_order', []);

        foreach ($passOrder as $passName) {
            if (! in_array($passName, $completedPasses, true)) {
                // Dispatch a job for each missing pass
                Log::info("Dispatching ProcessAnalysisPassJob for pass [{$passName}] => [{$analysis->file_path}].");
                ProcessAnalysisPassJob::dispatch(
                    codeAnalysisId: $analysis->id,
                    passName:       $passName,
                    dryRun:         $dryRun
                );
            }
        }
    }

    /**
     * Build a prompt for a specific pass (used by ProcessAnalysisPassJob).
     */
    public function buildPromptForPass(CodeAnalysis $analysis, string $passName): string
    {
        // 1) Get the pass config
        $allPassConfigs = config('ai.operations.multi_pass_analysis', []);
        $cfg = $allPassConfigs[$passName] ?? null;

        $passType = $cfg['type'] ?? 'both';
        $base     = $cfg['prompt'] ?? 'Analyze the following code:';

        $ast      = $analysis->ast ?? [];
        $rawCode  = $this->getRawCode($analysis->file_path);

        $prompt   = $base;

        // For normal pass types
        if ($passType === 'ast' || $passType === 'both') {
            $prompt .= "\n\nAST Data:\n" . json_encode($ast, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if ($passType === 'raw' || $passType === 'both') {
            $prompt .= "\n\nRaw Code:\n" . $rawCode;
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

        $prompt .= "\n\nRespond with structured insights.\n";
        return $prompt;
    }

    protected function getRawCode(string $filePath): string
    {
        try {
            return File::get($filePath);
        } catch (\Exception $ex) {
            Log::warning("Could not read file [{$filePath}]: " . $ex->getMessage());
            return '';
        }
    }
}