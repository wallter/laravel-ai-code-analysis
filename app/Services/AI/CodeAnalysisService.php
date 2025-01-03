<?php

namespace App\Services\AI;

use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;
use Exception;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

class CodeAnalysisService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Send an analysis request to OpenAI API.
     *
     * @param string $input
     * @return array
     */
    public function sendAnalysisRequest(string $input): array
    {
        try {
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
    public function analyzeAst(array $ast, int $limitMethod = 0): array
    {
        // Implement your AST analysis logic here.
        // For example, count classes, methods, functions, etc.

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver());
        $ast = $nodeTraverser->traverse($ast);

        $analysisResults = [
            'class_count' => 0,
            'method_count' => 0,
            'function_count' => 0,
            'classes' => [],
        ];

        foreach ($ast as $node) {
            if ($node instanceof \PhpParser\Node\Stmt\ClassLike) {
                $analysisResults['class_count']++;

                $className = $node->name ? $node->name->toString() : 'Anonymous Class';
                $methodCount = 0;
                $methods = [];

                foreach ($node->getMethods() as $method) {
                    if ($limitMethod > 0 && $methodCount >= $limitMethod) {
                        break;
                    }
                    $methodCount++;
                    $methods[] = $method->name->toString();
                }

                $analysisResults['method_count'] += $methodCount;

                $analysisResults['classes'][] = [
                    'name' => $className,
                    'methods' => $methods,
                ];
            }

            if ($node instanceof \PhpParser\Node\Stmt\Function_) {
                $analysisResults['function_count']++;
            }
        }

        return $analysisResults;
    }
}
