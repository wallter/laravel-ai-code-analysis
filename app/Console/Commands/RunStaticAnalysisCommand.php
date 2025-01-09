<?php

namespace App\Console\Commands;

use App\Models\CodeAnalysis;
use App\Services\StaticAnalysisService;
use Illuminate\Console\Command;

class RunStaticAnalysisCommand extends Command
{
    protected $signature = 'static-analysis:run {code_analysis_id}';
    protected $description = 'Run static analysis on a specific CodeAnalysis entry';

    protected StaticAnalysisService $staticAnalysisService;

    public function __construct(StaticAnalysisService $staticAnalysisService)
    {
        parent::__construct();
        $this->staticAnalysisService = $staticAnalysisService;
    }

    public function handle()
    {
        $codeAnalysisId = $this->argument('code_analysis_id');
        $codeAnalysis = CodeAnalysis::find($codeAnalysisId);

        if (!$codeAnalysis) {
            $this->error("CodeAnalysis with ID {$codeAnalysisId} not found.");
            return 1;
        }

        $this->info("Running static analysis on '{$codeAnalysis->file_path}'.");

        $staticAnalysis = $this->staticAnalysisService->runAnalysis($codeAnalysis);

        if ($staticAnalysis) {
            $this->info("Static analysis completed and results stored.");
        } else {
            $this->error("Static analysis failed.");
        }

        return 0;
    }
}
