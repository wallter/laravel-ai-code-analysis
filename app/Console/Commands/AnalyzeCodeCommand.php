<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\FunctionAndClassVisitor;
use App\Services\AI\CodeAnalysisService;
use App\Models\CodeAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyzeCodeCommand extends Command
{
    protected $signature = 'code:analyze 
                            {--output-file= : Where to output analysis results}
                            {--limit-class= : Limit how many classes to analyze}
                            {--limit-method= : Limit how many methods per class}';

    protected $description = 'Analyze PHP code, generate AST, and persist the analysis results';

    public function __construct(
        protected ParserService $parserService,
        protected CodeAnalysisService $codeAnalysisService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $phpFiles = $this->parserService->collectPhpFiles()->unique();
        if ($phpFiles->isEmpty()) {
            $this->error("No PHP files found to analyze.");
            return 1;
        }

        $outputFile   = $this->option('output-file') ?: null;
        $limitClass   = intval($this->option('limit-class'))   ?: 0;
        $limitMethod  = intval($this->option('limit-method'))  ?: 0;

        $this->info("Analyzing {$phpFiles->count()} file(s)...");

        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        DB::beginTransaction();

        $analysisResults = [];
        foreach ($phpFiles as $filePath) {
            try {
                // Create a new visitor each time (or reuse if you want)
                $visitor = new FunctionAndClassVisitor();

                // Parse file with that visitor
                $ast = $this->parserService->parseFile(
                    filePath: $filePath,
                    visitors: [$visitor],
                    useCache: false
                );

                // Now retrieve the data from the visitor
                $classes   = $visitor->getClasses();
                $functions = $visitor->getFunctions();

                // Additional analysis from codeAnalysisService
                $analysis = $this->codeAnalysisService->analyzeAstFromData($classes, $functions, [
                    'limitClass'  => $limitClass,
                    'limitMethod' => $limitMethod,
                ]);

                // Optionally store the combined “analysis” in CodeAnalysis
                CodeAnalysis::updateOrCreate(
                    ['file_path' => $filePath],
                    [
                        'ast'      => json_encode($ast),
                        'analysis' => json_encode($analysis),
                    ]
                );

                // For final output
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

        // Optionally write out a final results file
        if ($outputFile) {
            $this->writeResultsToFile($analysisResults, $outputFile);
            $this->info("Analysis results exported to {$outputFile}");
        }

        $this->info("Code analysis completed.");
        return 0;
    }

    private function writeResultsToFile(array $analysisResults, string $filePath): void
    {
        $json = json_encode($analysisResults, JSON_PRETTY_PRINT);
        if (!$json) {
            $this->error("Failed to JSON-encode results for output file.");
            return;
        }
        @mkdir(dirname($filePath), 0777, true);
        file_put_contents($filePath, $json);
    }
}