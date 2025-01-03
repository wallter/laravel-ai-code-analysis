<?php

namespace App\Services\AI;

use App\Services\Parsing\ParserService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Exception;

/**
 * CodeAnalysisService
 *
 * Gathers AST-based data, raw code, and applies multi-pass AI analysis.
 * Summarizes or refactors code as needed. 
 */
class CodeAnalysisService
{
    public function __construct(
        protected OpenAIService  $openAIService,
        protected ParserService  $parserService
    ) {}

    /**
     * Main analysis entry point for a single file. 
     * Returns an array of merged results from AST visitors & AI passes.
     *
     * @param string $filePath
     * @param int    $limitMethod
     * @return array
     */
    public function analyzeAst(string $filePath, int $limitMethod = 0): array
    {
        Log::info("CodeAnalysisService.analyzeAst => [{$filePath}], limitMethod={$limitMethod}");

        // 1) Parse & gather AST data
        $ast = $this->parserService->parseFile($filePath);
        if (empty($ast)) {
            Log::warning("No AST generated or file was empty: [{$filePath}]");
            return [];
        }

        // Gather AST-based data
        $astData = $this->collectAstData($ast, $limitMethod);

        // Retrieve the raw code
        $rawCode = $this->retrieveRawCode($filePath);

        // Perform multi-pass AI analysis; each pass becomes a separate key
        $multiPassResults = $this->performMultiPassAnalysis($astData, $rawCode);

        return [
            'ast_data'   => $astData,        // structured AST info
            'ai_results' => $multiPassResults, // e.g. { "doc_generation": "...", "security_assessment": "...", ... }
        ];
    }

    /**
     * Pass the AST to visitors, respecting $limitMethod for methods.
     */
    protected function collectAstData(array $ast, int $limitMethod): array
    {
        Log::debug("Collecting AST data with limitMethod={$limitMethod}");

        // Example of using a combined visitor that collects classes & functions
        $classVisitor    = new \App\Services\Parsing\FunctionAndClassVisitor();
        $functionVisitor = new \App\Services\Parsing\FunctionVisitor();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($classVisitor);
        $traverser->addVisitor($functionVisitor);
        $traverser->traverse($ast);

        $classes   = collect($classVisitor->getClasses());
        $functions = collect($functionVisitor->getFunctions());

        if ($classes->isEmpty() && $functions->isEmpty()) {
            Log::debug("AST contained no classes or functions. Possibly an empty file or pure statements.");
        }

        $analysis = [
            'class_count'    => $classes->count(),
            'function_count' => $functions->count(),
            'method_count'   => 0,
            'classes'        => [],
            'functions'      => [],
        ];

        // Build the 'classes' array, applying limitMethod if needed
        $analysis['classes'] = $classes->map(function ($cls) use ($limitMethod, &$analysis) {
            $methods = Arr::get($cls, 'details.methods', []);
            if ($limitMethod > 0 && count($methods) > $limitMethod) {
                $methods = array_slice($methods, 0, $limitMethod);
            }
            $analysis['method_count'] += count($methods);

            return [
                'name'    => Arr::get($cls, 'name', ''),
                'methods' => $methods,
            ];
        })->values()->all();

        // Store function data (if your FunctionVisitor collects free-floating functions)
        $analysis['functions'] = $functions->values()->all();

        Log::debug("Collected classes={$analysis['class_count']}, free-functions={$analysis['function_count']}, methods={$analysis['method_count']}");

        return $analysis;
    }

    /**
     * Load raw file contents for AI-based improvement suggestions.
     */
    protected function retrieveRawCode(string $filePath): string
    {
        try {
            return File::get($filePath);
        } catch (Exception $ex) {
            Log::warning("Could not read raw code from [{$filePath}]: " . $ex->getMessage());
            return '';
        }
    }

    /**
     * Repeatedly calls OpenAI with different prompts from config('ai.operations.multi_pass_analysis').
     * Merges each pass result into a final array.
     */
    protected function performMultiPassAnalysis(array $astData, string $rawCode): array
    {
        // This will hold each pass result keyed by passName, e.g. 'doc_generation', 'refactor_suggestions', etc.
        $results = [];

        // Retrieve passes from config
        $multiPasses = Config::get('ai.operations.multi_pass_analysis.multi_pass_analysis', []);

        // For each pass (e.g. doc_generation, refactor_suggestions, etc.)
        foreach ($multiPasses as $passName => $passCfg) {
            try {
                // Build a final prompt for this pass
                $prompt = $this->buildPrompt($astData, $rawCode, $passCfg['type'], $passCfg);

                // Send to OpenAI (or your AI provider) for analysis
                $responseText = $this->openAIService->performOperation($passCfg['operation'], [
                    'prompt'      => $prompt,
                    'max_tokens'  => $passCfg['max_tokens']  ?? 1024,
                    'temperature' => $passCfg['temperature'] ?? 0.5,
                    // Possibly override system message, etc.
                ]);

                // Store the raw text directly under the pass name
                $results[$passName] = $responseText;
            } catch (\Throwable $e) {
                Log::error("Pass [{$passName}] failed: " . $e->getMessage(), ['exception' => $e]);
                $results[$passName] = "(Error: {$e->getMessage()})";
            }
        }

        return $results;
    }

    /**
     * Builds a pass-specific prompt using the pass config plus either AST data, raw code, or both.
     */
    protected function buildPrompt(
        array  $astData,
        string $rawCode,
        string $type,
        array  $passCfg
    ): string {
        // Use a base prompt from config or fallback
        $basePrompt = Arr::get($passCfg, 'prompt', 'Analyze the code and provide insights:');
        $prompt = $basePrompt;

        if ($type === 'ast' || $type === 'both') {
            $prompt .= "\n\nAST Data:\n";
            $prompt .= json_encode($astData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if ($type === 'raw' || $type === 'both') {
            $prompt .= "\n\nRaw Code:\n" . $rawCode;
        }

        $prompt .= "\n\nPlease respond with thorough, structured insights.\n";
        return $prompt;
    }
}
