<?php

namespace App\Services\AI;

use App\Models\CodeAnalysis;
use App\Models\AIResult;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\UnifiedAstVisitor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Context;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

/**
 * CodeAnalysisService
 *  - Parses files into AST
 *  - Runs multi-pass AI analysis
 *  - Stores each pass result in AIResult
 */
class CodeAnalysisService
{
    public function __construct(
        protected OpenAIService $openAIService,
        protected ParserService $parserService
    ) {
    }

    /**
     * Run multi-pass analysis on a CodeAnalysis, storing each pass in AIResult.
     */
    public function runAnalysis(CodeAnalysis $codeAnalysis, bool $dryRun = false): void
    {
        // 1) Determine passes we haven't completed
        $completed = $codeAnalysis->completed_passes ?? [];
        if (!is_array($completed)) {
            $completed = json_decode($completed, true) ?? [];
        }

        $allPasses   = config('ai.operations.multi_pass_analysis.pass_order', []);
        $passDetails = config('ai.operations.multi_pass_analysis', []);
        foreach ($allPasses as $passName) {
            if (!in_array($passName, $completed, true)) {
                $this->processPass($codeAnalysis, $passName, $passDetails, $dryRun);
            }
        }
    }

    /**
     * Processes a single pass (doc_generation, performance_analysis, etc.).
     */
    protected function processPass(CodeAnalysis $codeAnalysis, string $passName, array $passDetails, bool $dryRun): void
    {
        $passConfig = $passDetails[$passName] ?? null;
        if (!$passConfig) {
            Log::warning("Pass [{$passName}] has no config.");
            return;
        }

        try {
            Context::add('pass_name', $passName);
            Context::add('file_path', $codeAnalysis->file_path);

            // Build prompt from AST + raw code
            $prompt = $this->buildPrompt(
                astData: $codeAnalysis->ast ?? [],
                rawCode: $this->getRawCode($codeAnalysis->file_path),
                passType: $passConfig['type'] ?? 'both',
                passCfg: $passConfig
            );

            if ($dryRun) {
                Log::info("[DRY-RUN] Skipping AI call for [{$passName}]");
                return;
            }

            // Actually call AI
            $responseText = $this->openAIService->performOperation(
                $passConfig['operation'] ?? 'code_analysis',
                [
                    'prompt'      => $prompt,
                    'max_tokens'  => $passConfig['max_tokens']  ?? 1500,
                    'temperature' => $passConfig['temperature'] ?? 0.5,
                ]
            );

            // Store in AIResult
            AIResult::create([
                'code_analysis_id' => $codeAnalysis->id,
                'pass_name'        => $passName,
                'prompt_text'      => $prompt,
                'response_text'    => $responseText,
            ]);

            // Update passes
            $completed = (array) ($codeAnalysis->completed_passes ?? []);
            $completed[] = $passName;
            $codeAnalysis->completed_passes = array_values($completed);
            $codeAnalysis->current_pass += 1;
            $codeAnalysis->save();

            Log::info("Pass [{$passName}] completed for [{$codeAnalysis->file_path}]");
        } catch (\Throwable $e) {
            Log::error("Failed pass [{$passName}] for [{$codeAnalysis->file_path}]: " . $e->getMessage());
        } finally {
            Context::forget('pass_name');
            Context::forget('file_path');
        }
    }

    /**
     * Optionally parse a single file if not already in DB, or re-parse if needed.
     */
    public function analyzeFile(string $filePath, bool $reparse = false): CodeAnalysis
    {
        $analysis = CodeAnalysis::firstOrCreate(
            ['file_path' => $filePath],
            ['ast' => [], 'analysis' => []]
        );

        if ($reparse || empty($analysis->ast)) {
            $ast = $this->parserService->parseFile($filePath);
            $analysis->ast = $ast;
            $analysis->analysis = $this->buildAstSummary($filePath, $ast);
            $analysis->save();
        }

        return $analysis;
    }

    /**
     * Summarize AST items for clarity, storing in 'analysis' field if desired.
     */
    protected function buildAstSummary(string $filePath, array $ast): array
    {
        // Use UnifiedAstVisitor to discover items
        $visitor = new UnifiedAstVisitor();
        $visitor->setCurrentFile($filePath);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $items = $visitor->getItems();
        $classes   = array_filter($items, fn($i) => in_array($i['type'], ['Class','Trait','Interface']));
        $functions = array_filter($items, fn($i) => $i['type'] === 'Function');

        return [
            'class_count'    => count($classes),
            'function_count' => count($functions),
            'items'          => array_values($items),
        ];
    }

    /**
     * Builds a final AI prompt using pass config (AST vs raw vs both).
     */
    protected function buildPrompt(array $astData, string $rawCode, string $passType, array $passCfg): string
    {
        $basePrompt = $passCfg['prompt'] ?? 'Analyze the code:';
        $prompt = $basePrompt;

        if ($passType === 'ast' || $passType === 'both') {
            $prompt .= "\n\nAST Data:\n";
            $prompt .= json_encode($astData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if ($passType === 'raw' || $passType === 'both') {
            $prompt .= "\n\nRaw Code:\n" . $rawCode;
        }

        $prompt .= "\n\nPlease provide structured, concise insights.";
        return $prompt;
    }

    protected function getRawCode(string $filePath): string
    {
        try {
            return File::get($filePath);
        } catch (\Exception $e) {
            Log::warning("Could not read code from [{$filePath}]: " . $e->getMessage());
            return '';
        }
    }
}