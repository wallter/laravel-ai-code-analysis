<?php

namespace App\Services\AI;

use App\Services\Parsing\ParserService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Node\Stmt\Function_;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use Exception;

/**
 * CodeAnalysisService
 *
 * Handles comprehensive code analysis by combining:
 *   • AST-based structural insights (classes, methods, etc.)
 *   • Raw code analysis
 *   • Multiple AI passes defined in config/ai.php
 *
 * The "multi_pass_analysis" config array defines how each pass is done, 
 * referencing either the AST or raw code, or both.
 *
 * Each pass merges results into a final structure returned to callers.
 */
class CodeAnalysisService
{
    public function __construct(
        protected OpenAIService $openAIService,
        protected ParserService $parserService
    ) {
        $this->printer = new PrettyPrinter();
    }

    /**
     * Analyze a single function's AST node.
     *
     * @param Function_ $function The function AST node
     * @return array Analysis data
     */
    public function analyzeFunctionAst(Function_ $function): array
    {
        $functionName = $function->name->toString();
        Log::debug("Starting analysis of function: {$functionName}");

        try {
            // Collect parameters
            $parameters = [];
            foreach ($function->params as $param) {
                $paramName = $param->var->name;
                $parameters[] = $paramName;
            }
            Log::debug("Function parameters: ", ['parameters' => $parameters]);

            // Collect return type
            $returnType = $function->getReturnType() ? $function->getReturnType()->toString() : 'void';
            Log::debug("Function return type: {$returnType}");

            // Get function body code
            $stmts = $function->getStmts();
            $functionBody = $stmts ? $this->printer->prettyPrint($stmts) : '';
            Log::debug("Function body code length: " . strlen($functionBody));

            // Use OpenAIService for AI analysis
            $analysisData = $this->openAIService->performOperation('analyze_function', [
                'function_name' => $functionName,
                'parameters'    => $parameters,
                'return_type'   => $returnType,
                'function_body' => $functionBody,
            ]);

            Log::debug("Completed analysis of function: {$functionName}", [
                'analysisData' => $analysisData,
            ]);

            return $analysisData;
        } catch (\Exception $e) {
            Log::error("Error analyzing function: {$functionName}", [
                'exception' => $e,
            ]);
            return [];
        }
    }

    /**
     * Perform the analysis on a single file path.
     * 1) Parse the file => get AST
     * 2) Gather structural data (class_count, function_count, etc.)
     * 3) Retrieve raw code
     * 4) Perform multi-pass AI analysis (configured in ai.php)
     *
     * @param string $filePath
     * @param int    $limitMethod  Restrict how many methods to process per class
     *
     * @return array Combined final analysis results
     */
    public function analyzeAst(string $filePath, int $limitMethod = 0): array
    {
        Log::debug("Starting analyzeAst for [{$filePath}], limitMethod={$limitMethod}");

        // 1) Parse AST
        $ast = $this->parserService->parseFile($filePath);

        // 2) Collect structural data from the AST
        $astData = $this->collectAstData($ast, $limitMethod);

        // 3) Retrieve raw code
        $rawCode = $this->retrieveRawCode($filePath);

        // 4) Multi-pass AI analysis => merges with $astData
        $multiPassResults = $this->performMultiPassAnalysis($astData, $rawCode);

        $analysisResults = array_merge($astData, $multiPassResults);

        Log::debug("Completed analysis for [{$filePath}]");
        return $analysisResults;
    }

    /**
     * Uses visitors (FunctionAndClassVisitor, FunctionVisitor, etc.) 
     * to produce basic structural data. Respects $limitMethod.
     */
    protected function collectAstData(array $ast, int $limitMethod): array
    {
        $nodeTraverser = new NodeTraverser();

        // Example: We have a combined visitor or multiple
        $classVisitor = new \App\Services\Parsing\FunctionAndClassVisitor();
        $functionVisitor = new \App\Services\Parsing\FunctionVisitor();

        $nodeTraverser->addVisitor(new NameResolver());
        $nodeTraverser->addVisitor($classVisitor);
        $nodeTraverser->addVisitor($functionVisitor);
        $nodeTraverser->traverse($ast);

        $classes   = collect($classVisitor->getClasses());
        $functions = collect($functionVisitor->getFunctions());

        $analysis = [
            'class_count'    => $classes->count(),
            'method_count'   => 0,
            'function_count' => $functions->count(),
            'classes'        => [],
            'functions'      => $functions->values()->all(),
        ];

        $analysis['classes'] = $classes->map(function ($class) use ($limitMethod, &$analysis) {
            // E.g. the visitor places an array of methods under $class['details']['methods']
            $methods = Arr::get($class, 'details.methods', []);
            if ($limitMethod > 0 && count($methods) > $limitMethod) {
                $methods = array_slice($methods, 0, $limitMethod);
            }
            $analysis['method_count'] += count($methods);

            return [
                'name'    => Arr::get($class, 'name', ''),
                'methods' => $methods,
            ];
        })->values()->all();

        return $analysis;
    }

    /**
     * Reads the raw file content.
     */
    protected function retrieveRawCode(string $filePath): string
    {
        try {
            return File::get($filePath);
        } catch (\Throwable $t) {
            Log::warning("Failed to retrieve file contents [{$filePath}]: " . $t->getMessage());
            return '';
        }
    }

    /**
     * Looks up multi-pass definitions from config('ai.operations.multi_pass_analysis')
     * and processes each pass accordingly, returning a combined array of results.
     */
    protected function performMultiPassAnalysis(array $astData, string $rawCode): array
    {
        $results = [];
        $multiPasses = Config::get('ai.operations.multi_pass_analysis', []);

        if (empty($multiPasses)) {
            Log::debug('No multi_pass_analysis config found, skipping advanced passes.');
            return $results;
        }

        foreach ($multiPasses as $passName => $passConfig) {
            try {
                // Each pass has an 'operation' and 'prompt' structure, plus
                // whether it references 'ast', 'raw', or 'both'.
                $operation = Arr::get($passConfig, 'operation', 'code_analysis');
                $modelType = Arr::get($passConfig, 'type', 'both'); // 'ast', 'raw', or 'both'
                $maxTokens = Arr::get($passConfig, 'max_tokens', 800);
                $temp      = Arr::get($passConfig, 'temperature', 0.5);

                // Build the dynamic prompt
                $prompt = $this->buildPromptForPass($astData, $rawCode, $modelType, $passConfig);

                $response = $this->openAIService->performOperation($operation, [
                    'prompt'     => $prompt,
                    'max_tokens' => $maxTokens,
                    'temperature'=> $temp,
                ]);

                $results[$passName] = $response;
            } catch (Exception $ex) {
                Log::error("Multi-pass [{$passName}] failed: " . $ex->getMessage());
                $results[$passName] = ''; // or store error
            }
        }

        return $results;
    }

    /**
     * Builds a prompt string suitable for a single pass from multi_pass_analysis config.
     */
    protected function buildPromptForPass(
        array  $astData,
        string $rawCode,
        string $modelType,
        array  $passConfig
    ): string {
        // Optional base template
        $basePrompt = Arr::get($passConfig, 'prompt', 'Analyze the data and provide insights:');
        $prompt = $basePrompt;

        if ($modelType === 'ast' || $modelType === 'both') {
            $prompt .= "\n\nAST data:\n" . json_encode($astData, JSON_PRETTY_PRINT);
        }
        if ($modelType === 'raw' || $modelType === 'both') {
            $prompt .= "\n\nRaw code:\n" . $rawCode;
        }

        // Possibly add more instructions or disclaimers
        $prompt .= "\n\nRespond with clear, actionable insights.";

        return $prompt;
    }
}
