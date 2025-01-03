<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Parsing\ParserService;
use App\Services\AI\CodeAnalysisService;
use App\Models\CodeAnalysis;

/**
 * Analyze code using AST parsing + AI. 
 * Extends BaseCodeCommand to unify shared options and logic.
 */
class AnalyzeCodeCommand extends BaseCodeCommand
{
    /**
     * We override just the command name portion, 
     * reusing the parent's options in the parent $signature.
     */
    protected $signature = 'code:analyze 
        {--output-file=}
        {--limit-class=}
        {--limit-method=}';

    protected $description = 'Analyze PHP code, generate AST, and persist the analysis results.';

    public function __construct(
        protected ParserService $parserService,
        protected CodeAnalysisService $codeAnalysisService
    ) {
        parent::__construct();
    }

    /**
     * Our child's main logic, called by BaseCodeCommand::handle().
     */
    protected function executeCommand(): int
    {
        $phpFiles = $this->parserService->collectPhpFiles()->unique();
        if ($phpFiles->isEmpty()) {
            $this->error("No PHP files found to analyze.");
            return 1;
        }

        // Get the final .json output file path, if any
        $outputFile   = $this->getOutputFile();
        $limitClass   = $this->getClassLimit();
        $limitMethod  = $this->getMethodLimit();

        $this->info("Analyzing {$phpFiles->count()} file(s)...");

        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        DB::beginTransaction();

        $analysisResults = [];
        foreach ($phpFiles as $filePath) {
            try {
                // (Example) parse or do something...
                // Here we might call "parseFile" with a FunctionAndClassVisitor, 
                // or directly call codeAnalysisService->analyzeAst($filePath, $limitMethod)
                $analysis = $this->codeAnalysisService->analyzeAst($filePath, $limitMethod);

                // Possibly store in DB
                CodeAnalysis::updateOrCreate(
                    ['file_path' => $filePath],
                    [
                        'ast'      => json_encode([]), // or an actual AST
                        'analysis' => json_encode($analysis),
                    ]
                );

                $analysisResults[$filePath] = $analysis;

                $this->info("Analyzed: {$filePath}");
            } catch (\Throwable $e) {
                Log::error("Analysis failed for {$filePath}: " . $e->getMessage());
                $this->error("Failed: {$filePath}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->line('');
        DB::commit();

        if ($outputFile) {
            $this->exportResults($analysisResults, $outputFile);
            $this->info("Analysis results exported to {$outputFile}");
        }

        $this->info("Code analysis completed.");
        return 0;
    }

    /**
     * Example method to export results to JSON.
     */
    protected function exportResults(array $analysisResults, string $filePath): void
    {
        $json = json_encode($analysisResults, JSON_PRETTY_PRINT);
        if (!$json) {
            $this->error("Failed to encode to JSON.");
            return;
        }
        @mkdir(dirname($filePath), 0777, true);
        file_put_contents($filePath, $json);
    }
}