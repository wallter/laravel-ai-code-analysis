<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIndividualPassJob;
use App\Models\CodeAnalysis;
use App\Services\StaticAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class RunStaticAnalysisCommand extends Command
{
    protected $signature = 'static-analysis:run';

    protected $description = 'Run static analysis on all CodeAnalysis entries without existing static analyses';

    protected array $enabledTools;

    public function __construct(protected StaticAnalysisService $staticAnalysisService)
    {
        parent::__construct();

        // Initialize the list of enabled static analysis tools
        $this->enabledTools = $this->getEnabledStaticAnalysisTools();
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

        $toolsByLanguage = [];

        foreach ($this->enabledTools as $toolName => $toolConfig) {
            $language = $toolConfig['language'] ?? 'unknown';
            $toolsByLanguage[$language][$toolName] = $toolConfig;
        }

        foreach ($codeAnalyses as $codeAnalysis) {
            // Handle multi-pass analysis if enabled
            if (Config::get('static_analysis.multi_pass_analysis.enabled')) {
                foreach (Config::get('static_analysis.multi_pass_analysis.passes', []) as $passName => $passConfig) {
                    $this->info("Dispatching pass '{$passName}' for '{$codeAnalysis->file_path}'.");
                    ProcessIndividualPassJob::dispatch($codeAnalysis->id, $passName, $dryRun = false);
                }

                $this->info('Multi-pass analysis jobs have been dispatched.');

                continue;
            }

            $language = $codeAnalysis->language;

            if (empty($language)) {
                // Determine language from file extension
                $extension = strtolower(pathinfo($codeAnalysis->file_path, PATHINFO_EXTENSION));
                $languageMap = [
                    'php' => 'php',
                    'js' => 'javascript',
                    'ts' => 'typescript',
                    'py' => 'python',
                    'go' => 'go',
                    'ex' => 'elixir',
                    'exs' => 'elixir',
                ];
                $language = $languageMap[$extension] ?? 'unknown';
                $codeAnalysis->language = $language;
                $codeAnalysis->save();

                $this->info("Determined language '{$language}' for '{$codeAnalysis->file_path}' and updated the CodeAnalysis entry.");
            }

            if (! isset($toolsByLanguage[$language])) {
                $this->warn("No static analysis tools configured for language '{$language}' detected in '{$codeAnalysis->file_path}'. Skipping.");

                continue;
            }

            foreach ($toolsByLanguage[$language] as $toolName => $toolConfig) {
                $this->info("Running static analysis '{$toolName}' on '{$codeAnalysis->file_path}'.");

                $staticAnalysis = $this->staticAnalysisService->runAnalysis($codeAnalysis, $toolName);

                if ($staticAnalysis) {
                    $this->info("Static analysis '{$toolName}' completed and results stored for '{$codeAnalysis->file_path}'.");
                } else {
                    $this->error("Static analysis '{$toolName}' failed for '{$codeAnalysis->file_path}'.");
                }
            }
        }

        $this->info('All pending static analyses have been processed.');

        return 0;
    }

    /**
     * Get the list of enabled static analysis tools from configuration.
     */
    protected function getEnabledStaticAnalysisTools(): array
    {
        $toolsConfig = Config::get('static_analysis.tools', []);
        $enabledTools = [];

        foreach ($toolsConfig as $toolName => $toolSettings) {
            if ($toolSettings['enabled'] ?? false) {
                $enabledTools[$toolName] = $toolSettings;
            }
        }

        return $enabledTools;
    }
}
