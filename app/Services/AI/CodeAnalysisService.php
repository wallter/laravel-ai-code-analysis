<?php

namespace App\Services\AI;

use App\Models\CodeAnalysis;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\UnifiedAstVisitor;
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
     * Process the next AI pass for a given CodeAnalysis record.
     *
     * @param CodeAnalysis $codeAnalysis
     * @return void
     */
    public function processNextPass(CodeAnalysis $codeAnalysis): void
    {
        // Ensure 'completed_passes' is an array
        $completedPasses = $codeAnalysis->completed_passes ?? [];
        if (!is_array($completedPasses)) {
            $completedPasses = json_decode($completedPasses, true) ?? [];
        }

        $passOrder = config('ai.operations.multi_pass_analysis.pass_order', []);
        $passOrderCount = count($passOrder);
        $multiPasses = config('ai.operations.multi_pass_analysis.multi_pass_analysis', []);

        // Determine the next pass to execute
        $nextPass = null;
        foreach ($passOrder as $passName) {
            if (!in_array($passName, $completedPasses)) {
                $nextPass = $passName;
                break;
            }
        }

        if (!$nextPass) {
            info("All passes completed for [{$codeAnalysis->file_path}].");
            return;
        }

        $passConfig = $multiPasses[$nextPass] ?? null;
        if (!$passConfig) {
            Log::error("Pass [{$nextPass}] not defined in configuration.");
            return;
        }

        try {
            // Build the prompt based on the pass type
            $prompt = $this->buildPrompt(
                json_decode($codeAnalysis->ast, true),
                $this->retrieveRawCode($codeAnalysis->file_path),
                $passConfig['type'],
                $passConfig
            );

            // Perform the AI operation
            $responseText = $this->openAIService->performOperation($passConfig['operation'], [
                'prompt'      => $prompt,
                'max_tokens'  => $passConfig['max_tokens'] ?? 1024,
                'temperature' => $passConfig['temperature'] ?? 0.5,
            ]);

            // Append the response to ai_output
            $aiOutput = json_decode($codeAnalysis->ai_output, true) ?? [];
            $aiOutput[$nextPass] = $responseText;
            $codeAnalysis->ai_output = json_encode($aiOutput, JSON_UNESCAPED_SLASHES);

            // Update completed_passes and current_pass
            $completedPasses[] = $nextPass;
            $codeAnalysis->completed_passes = $completedPasses;
            $codeAnalysis->current_pass += 1;

            $codeAnalysis->save();

            info("Pass [{$nextPass}] completed for [{$codeAnalysis->file_path}].");
        } catch (\Throwable $e) {
            Log::error("Failed to perform pass [{$nextPass}] for [{$codeAnalysis->file_path}]: {$e->getMessage()}", ['exception' => $e]);
            $this->error("Failed to process pass for [{$codeAnalysis->file_path}]: {$e->getMessage()}");
        }
    }

    /**
     * Build the prompt for the AI based on the pass configuration.
     *
     * @param array $astData
     * @param string $rawCode
     * @param string $type
     * @param array $passConfig
     * @return string
     */
    // parseFile is your ParserService method that runs the AST parse
    public function analyzeAst(string $filePath, int $limitMethod): array
    {
        $ast = $this->parserService->parseFile($filePath);
        if (empty($ast)) {
            return [];
        }

        // Now use your single visitor
        $visitor = new UnifiedAstVisitor();
        $visitor->setCurrentFile($filePath);

        $traverser = new \PhpParser\NodeTraverser();
        $traverser->addVisitor(new NameResolver()); // optional
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        // The "items" contain both classes + methods + free-floating functions
        $items = $visitor->getItems();

        // Summarize AST quickly:
        $astData = $this->buildSummary($items, $limitMethod);

        // Raw code for AI passes
        $rawCode = $this->retrieveRawCode($filePath);

        // Perform multi-pass
        $multiPassResults = $this->performMultiPassAnalysis($astData, $rawCode);

        return [
            'ast_data'   => $astData,
            'ai_results' => $multiPassResults,
        ];
    }

    /**
     * Retrieve raw code from the file system.
     *
     * @param string $filePath
     * @return string
     */
    protected function retrieveRawCode(string $filePath): string
    {
        try {
            return File::get($filePath);
        } catch (\Exception $ex) {
            Log::warning("Could not read raw code from [{$filePath}]: " . $ex->getMessage());
            return '';
        }
    }

    /**
     * A shorter summary—just a count, plus classes & functions with docblock data.
     */
    protected function buildSummary(array $items, int $limitMethod): array
    {
        $classes   = array_filter($items, fn($it) => $it['type'] === 'Class');
        $functions = array_filter($items, fn($it) => $it['type'] === 'Function');

        $methodCount = 0;
        $classData = [];
        foreach ($classes as $cls) {
            $allMethods = $cls['details']['methods'] ?? [];
            if ($limitMethod > 0 && \count($allMethods) > $limitMethod) {
                $allMethods = \array_slice($allMethods, 0, $limitMethod);
            }
            $methodCount += \count($allMethods);

            $classData[] = [
                'name'        => $cls['name'],
                'namespace'   => $cls['namespace'] ?? '',
                'annotations' => $cls['annotations'] ?? [],
                'description' => $cls['details']['description'] ?? '',
                'methods'     => $allMethods,
            ];
        }

        return [
            'class_count'    => \count($classData),
            'function_count' => \count($functions),
            'method_count'   => $methodCount,
            'classes'        => array_values($classData),
            'functions'      => array_values($functions),
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
     * Repeatedly calls OpenAI with different prompts from config('ai.operations.multi_pass_analysis').
     * Merges each pass result into a final array.
     */
    protected function performMultiPassAnalysis(array $astData, string $rawCode): array
    {
        // This will hold each pass result keyed by passName, e.g. 'doc_generation', 'refactor_suggestions', etc.
        $results = [];

        // Retrieve passes from config
        $multiPasses = config('ai.operations.multi_pass_analysis.multi_pass_analysis', []);

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
