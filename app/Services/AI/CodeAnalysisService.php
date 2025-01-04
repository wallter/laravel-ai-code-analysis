<?php

namespace App\Services\AI;

use App\Models\CodeAnalysis;
use App\Models\ParsedItem;
use App\Models\AiResult;
use App\Services\Parsing\ParserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Context;
use Exception;
use Illuminate\Database\Eloquent\Model;

class CodeAnalysisService
{
    /**
     * Constructor now expects OpenAIService first, then ParserService.
     *
     * @param OpenAIService $openAIService
     * @param ParserService $parserService
     */
    public function __construct(
        protected OpenAIService  $openAIService,
        protected ParserService  $parserService
    ) {}

    /**
     * Process the next AI pass for a given CodeAnalysis record.
     *
     * @param CodeAnalysis $codeAnalysis
     * @param bool $dryRun
     * @return void
     */
    public function processNextPass(CodeAnalysis $codeAnalysis, bool $dryRun = false): void
    {
        // Existing implementation...
        // Ensure relationships are properly utilized
    }

    /**
     * Analyze AST and perform multi-pass AI analysis.
     *
     * @param string $filePath
     * @param int $limitMethod
     * @return array
     */
    public function analyzeAst(string $filePath, int $limitMethod): array
    {
        $parsedItem = ParsedItem::firstOrCreate(['file_path' => $filePath]);

        $ast = $this->parserService->parseFile($filePath);
        if (empty($ast)) {
            return [];
        }

        // Now use your single visitor
        $visitor = new UnifiedAstVisitor();
        $visitor->setCurrentFile($filePath);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver()); // optional
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        // The "items" contain both classes + methods + free-floating functions
        $items = $visitor->getItems();

        // Summarize AST quickly:
        $astData = $this->buildSummary($items, $limitMethod);

        // Raw code for AI passes
        $rawCode = $this->retrieveRawCode($filePath);

        // Create CodeAnalysis associated with ParsedItem
        $codeAnalysis = $parsedItem->codeAnalysis()->create([
            'file_path' => $filePath,
            'ast' => $astData,
            'analysis' => [], // Initialize as needed
            'ai_output' => null,
            'current_pass' => 0,
            'completed_passes' => [],
        ]);

        // Perform multi-pass
        $multiPassResults = $this->performMultiPassAnalysis($astData, $rawCode);

        // Associate AiResults with CodeAnalysis and ParsedItem
        foreach ($multiPassResults as $passName => $result) {
            $codeAnalysis->aiResults()->create([
                'analysis' => $result,
            ]);
        }

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
        // Existing implementation...
    }

    /**
     * Pass the AST to visitors, respecting $limitMethod for methods.
     */
    protected function collectAstData(array $ast, int $limitMethod): array
    {
        // Existing implementation...
    }

    /**
     * Repeatedly calls OpenAI with different prompts from config('ai.operations.multi_pass_analysis').
     * Merges each pass result into a final array.
     */
    protected function performMultiPassAnalysis(array $astData, string $rawCode): array
    {
        // Existing implementation...
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
        // Existing implementation...
    }
}
