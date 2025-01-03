<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Parsing\ParserService;
use App\Services\AI\CodeAnalysisService;
use App\Models\CodeAnalysis; // Assuming a model for persistence
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class AnalyzeCodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:analyze {directory} 
                                {--output-file= : Specify the output file for the analysis results}
                                {--limit-class= : Limit analysis to a specific number of classes}
                                {--limit-method= : Limit analysis to a specific number of methods per class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze PHP code, generate AST, and persist the analysis results';

    protected ParserService $parserService;
    protected CodeAnalysisService $codeAnalysisService;

    /**
     * Create a new command instance.
     *
     * @param ParserService $parserService
     * @param CodeAnalysisService $codeAnalysisService
     */
    public function __construct(ParserService $parserService, CodeAnalysisService $codeAnalysisService)
    {
        parent::__construct();
        $this->parserService = $parserService;
        $this->codeAnalysisService = $codeAnalysisService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $directory = $this->argument('directory');

        if (!is_dir($directory)) {
            $this->error("The directory '{$directory}' does not exist.");
            return 1;
        }

        // Retrieve options
        $outputFile = $this->option('output-file');
        $limitClass = intval($this->option('limit-class')) ?: Config::get('ai.operations.analysis_limits.limit_class', 0);
        $limitMethod = intval($this->option('limit-method')) ?: Config::get('ai.operations.analysis_limits.limit_method', 0);

        $this->info("Starting analysis for directory: {$directory}");

        // Retrieve PHP files using ParserService
        $phpFiles = $this->parserService->getPhpFiles($directory);

        // Apply limits if set
        if ($limitClass > 0) {
            $phpFiles = array_slice($phpFiles, 0, $limitClass);
        }

        $fileCount = count($phpFiles);
        $bar = $this->output->createProgressBar($fileCount);
        $bar->start();

        DB::beginTransaction();

        $analysisResults = [];

        foreach ($phpFiles as $filePath) {
            try {
                // Analyze the AST using CodeAnalysisService
                $analysis = $this->codeAnalysisService->analyzeAst($filePath, $limitMethod);

                // Retrieve the AST from ParserService
                $ast = $this->parserService->parseFile($filePath);

                // Persist the analysis and AST using updateOrCreate
                CodeAnalysis::updateOrCreate(
                    ['file_path' => $this->parserService->normalizePath($filePath)],
                    [
                        'ast' => json_encode($ast),
                        'analysis' => json_encode($analysis),
                    ]
                );

                if ($outputFile) {
                    $analysisResults[$filePath] = $analysis;
                }

                $this->info("Successfully analyzed and persisted: {$filePath}");
            } catch (\Exception $e) {
                Log::error("Analysis failed for {$filePath}: " . $e->getMessage());
                $this->error("Failed to analyze: {$filePath}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($outputFile) {
            $this->exportResults($outputFile, $analysisResults);
            $this->info("Analysis results exported to {$outputFile}");
        }

        DB::commit();

        $this->info("Code analysis completed.");

        return 0;
    }
    /**
     * Export analysis results to a specified output file.
     *
     * @param string $filePath
     * @param array $data
     * @return void
     */
    protected function exportResults(string $filePath, array $data): void
    {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        if (file_put_contents($filePath, $jsonData) === false) {
            $this->error("Failed to write analysis results to {$filePath}");
            Log::error("Failed to write analysis results to {$filePath}");
        }
    }
}
