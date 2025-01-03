<?php

namespace App\Services\AI;

use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;
use App\Services\Parsing\ParserService;
use Exception;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

class CodeAnalysisService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService, ParserService $parserService)
    {
        $this->openAIService = $openAIService;
        $this->parserService = $parserService;
    }

    protected ParserService $parserService;

    /**
     * Send an analysis request to OpenAI API.
     *
     * @param string $input
     * @return array
     */
    public function sendAnalysisRequest(string $input): array
    {
        try {
            // Example usage of ParserService to normalize input if it's a file path
            if (is_string($input) && file_exists($input)) {
                $input = $this->parserService->normalizePath($input);
            }
            $responseText = $this->openAIService->performOperation('code_analysis', [
                'prompt' => $input,
            ]);

            return [
                'summary' => $responseText,
                'tokens'  => 0, // Tokens usage not tracked in OpenAIService
            ];
        } catch (Exception $e) {
            Log::error('Failed to get a valid response from OpenAI.', ['exception' => $e]);
            return [];
        }
    }

    /**
     * Analyze the given AST and return analysis results.
     *
     * @param array $ast
     * @param int $limitMethod
     * @return array
     */
    public function analyzeAst(string $filePath, int $limitMethod = 0): array
    {
        // Use ParserService to parse the file
        $ast = $this->parserService->parseFile($filePath);

        // Initialize visitors
        $classVisitor = new \App\Services\Parsing\FunctionAndClassVisitor();
        $functionVisitor = new \App\Services\Parsing\FunctionVisitor();

        // Traverse AST with visitors
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver());
        $nodeTraverser->addVisitor($classVisitor);
        $nodeTraverser->addVisitor($functionVisitor);

        $nodeTraverser->traverse($ast);

        // Collect data from visitors
        $classes = $classVisitor->getClasses();
        $functions = $functionVisitor->getFunctions();

        $analysisResults = [
            'class_count' => count($classes),
            'method_count' => 0,
            'function_count' => count($functions),
            'classes' => [],
            'functions' => $functions,
        ];

        foreach ($classes as $class) {
            // Apply method limit
            $methods = $class['details']['methods'];
            if ($limitMethod > 0) {
                $methods = array_slice($methods, 0, $limitMethod);
            }
            $methodCount = count($methods);
            $analysisResults['method_count'] += $methodCount;

            $analysisResults['classes'][] = [
                'name' => $class['name'],
                'methods' => $methods,
            ];
        }

        return $analysisResults;
    }
}
