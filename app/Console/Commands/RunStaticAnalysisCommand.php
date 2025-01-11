<?php

namespace App\Console\Commands;

use App\Models\CodeAnalysis;
use App\Services\StaticAnalysisService;
use Illuminate\Console\Command;

class RunStaticAnalysisCommand extends Command
{
    protected $signature = 'static-analysis:run';

    protected $description = 'Run static analysis on all CodeAnalysis entries without existing static analyses';

    public function __construct(protected StaticAnalysisService $staticAnalysisService)
    {
        parent::__construct();
    }

    public function handle()
    {
        // Retrieve all CodeAnalysis entries that do not have any associated static analyses
        $codeAnalyses = CodeAnalysis::whereDoesntHave('staticAnalyses')->get();

        if ($codeAnalyses->isEmpty()) {
            $this->info('No CodeAnalysis entries found without static analyses.');
            return 0;
        }

        $this->info("Found [{$codeAnalyses->count()}] CodeAnalysis entries without static analyses.");

        foreach ($codeAnalyses as $codeAnalysis) {
            $this->info("Running static analysis on '{$codeAnalysis->file_path}'.");

            $staticAnalysis = $this->staticAnalysisService->runAnalysis($codeAnalysis);

            if ($staticAnalysis) {
                $this->info("Static analysis completed and results stored for '{$codeAnalysis->file_path}'.");
            } else {
                $this->error("Static analysis failed for '{$codeAnalysis->file_path}'.");
            }
        }

        $this->info('All pending static analyses have been processed.');

        return 0;
    }
}
