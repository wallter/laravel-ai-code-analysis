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
    ) {
    }

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

        $astData = $this->collectAstData($ast, $limitMethod);

        // 2) Raw code
        $rawCode = $this->retrieveRawCode($filePath);

        // 3) Multi-pass AI analysis
        $aiResults = $this->performMultiPassAnalysis($astData, $rawCode);

        return [
            'ast_data'    => $astData,    // Contains AST-related information
            'ai_results'  => $aiResults,  // Contains AI-generated outputs like doc_generation, etc.
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
        $results    = [];
        $multiPass  = Config::get('ai.operations.multi_pass_analysis', []);

        if (empty($multiPass)) {
            Log::debug("No multi_pass_analysis found in config. Skipping AI passes.");
            return $results;
        }

        foreach ($multiPass as $passName => $passCfg) {
            Log::info("Performing multi-pass analysis: [{$passName}]", $passCfg);
            
            try {
                $operation = Arr::get($passCfg, 'operation', 'code_analysis');
                $type      = Arr::get($passCfg, 'type', 'both'); // 'ast', 'raw', or 'both'
                $maxTokens = Arr::get($passCfg, 'max_tokens', 1024);
                $temp      = Arr::get($passCfg, 'temperature', 0.5);

                $prompt = $this->buildPrompt($astData, $rawCode, $type, $passCfg);
                $aiResponse = $this->openAIService->performOperation($operation, [
                    'prompt'     => $prompt,
                    'max_tokens' => $maxTokens,
                    'temperature'=> $temp,
                ]);

                $results[$passName] = $aiResponse;
            } catch (Exception $e) {
                Log::error("Pass [{$passName}] failed: " . $e->getMessage());
                $results[$passName] = '';
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
