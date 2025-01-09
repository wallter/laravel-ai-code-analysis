<?php

namespace App\Services;

use App\Models\CodeAnalysis;
use App\Models\StaticAnalysis;
use App\Services\StaticAnalysis\StaticAnalysisToolInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class StaticAnalysisService implements StaticAnalysisToolInterface
{
    /**
     * Run static analysis on the given file using the specified tool and store results.
     */
    public function runAnalysis(CodeAnalysis $codeAnalysis, string $toolName): ?StaticAnalysis
    {
        $filePath = $codeAnalysis->file_path;
        Log::info("StaticAnalysisService: Running {$toolName} on '{$filePath}'.");

        $toolsConfig = Config::get('ai.static_analysis_tools');

        if (! isset($toolsConfig[$toolName])) {
            Log::error("StaticAnalysisService: Static analysis tool '{$toolName}' is not configured.");

            return null;
        }

        $toolConfig = $toolsConfig[$toolName];

        if (! ($toolConfig['enabled'] ?? false)) {
            Log::warning("StaticAnalysisService: Static analysis tool '{$toolName}' is disabled.");

            return null;
        }

        $command = array_merge(
            [$toolConfig['command']],
            $toolConfig['options'] ?? [],
            [$filePath] // Added the file path to the command
        );

        Log::debug('StaticAnalysisService: Executing command - '.implode(' ', $command));

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            Log::error("StaticAnalysisService: {$toolName} failed for '{$filePath}'. Error: {$errorOutput}");

            return null;
        }

        $output = $process->getOutput();
        $results = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("StaticAnalysisService: Failed to decode JSON output from {$toolName} for '{$filePath}'. Error: ".json_last_error_msg());

            return null;
        }

        Log::debug("StaticAnalysisService: {$toolName} results for '{$filePath}': ".json_encode($results));

        $staticAnalysis = StaticAnalysis::create([
            'code_analysis_id' => $codeAnalysis->id,
            'tool' => $toolName,
            'results' => $results,
        ]);

        Log::info("StaticAnalysisService: {$toolName} analysis stored for '{$filePath}'.");

        return $staticAnalysis;
    }
}
