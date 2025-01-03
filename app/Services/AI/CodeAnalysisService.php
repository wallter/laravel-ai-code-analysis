<?php

namespace App\Services\AI;

use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;
use Exception;

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
     * @return array
     */
    public function analyzeAst(array $ast): array
    {
        // Implement your AST analysis logic here.
        // For example, count classes, methods, functions, etc.

        $classCount = 0;
        $methodCount = 0;
        $functionCount = 0;

        $nodeQueue = $ast;

        while (!empty($nodeQueue)) {
            $node = array_shift($nodeQueue);

            if ($node instanceof \PhpParser\Node\Stmt\ClassLike) {
                $classCount++;
                foreach ($node->getMethods() as $method) {
                    $methodCount++;
                }
            }

            if ($node instanceof \PhpParser\Node\Stmt\Function_) {
                $functionCount++;
            }

            // Add child nodes to the queue for further traversal
            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->$subNodeName;
                if (is_array($subNode)) {
                    foreach ($subNode as $childNode) {
                        if ($childNode instanceof \PhpParser\Node) {
                            $nodeQueue[] = $childNode;
                        }
                    }
                } elseif ($subNode instanceof \PhpParser\Node) {
                    $nodeQueue[] = $subNode;
                }
            }
        }

        return [
            'class_count' => $classCount,
            'method_count' => $methodCount,
            'function_count' => $functionCount,
            // Add more analysis metrics as needed
        ];
    }
}