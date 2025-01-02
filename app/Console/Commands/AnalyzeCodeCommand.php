<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Parsing\ParserService;
use App\Services\AI\CodeAnalysisService;
use App\Models\CodeAnalysis; // Assuming a model for persistence
use Illuminate\Support\Facades\Log;

class AnalyzeCodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:analyze {directory}';

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

        $this->info("Starting analysis for directory: {$directory}");

        $phpFiles = $this->parserService->getPhpFiles($directory);

        foreach ($phpFiles as $filePath) {
            try {
                $ast = $this->parserService->parseFile($filePath);
                $analysis = $this->codeAnalysisService->analyzeAst($ast);

                // Persist the analysis
                CodeAnalysis::create([
                    'file_path' => $this->parserService->normalizePath($filePath),
                    'ast' => json_encode($ast),
                    'analysis' => json_encode($analysis),
                ]);

                $this->info("Successfully analyzed and persisted: {$filePath}");
            } catch (\Exception $e) {
                Log::error("Analysis failed for {$filePath}: " . $e->getMessage());
                $this->error("Failed to analyze: {$filePath}");
            }
        }

        $this->info("Code analysis completed.");

        return 0;
    }
}
