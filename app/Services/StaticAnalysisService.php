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

        $toolsConfig = Config::get('static_analysis.tools', []);

        if (!isset($toolsConfig[$toolName])) {
            Log::error("StaticAnalysisService: Static analysis tool '{$toolName}' is not configured.");
            return null;
        }

        $toolConfig = $toolsConfig[$toolName];

        if (!($toolConfig['enabled'] ?? false)) {
            Log::warning("StaticAnalysisService: Static analysis tool '{$toolName}' is disabled.");
            return null;
        }

        $command = $toolConfig['command'];
        $options = $toolConfig['options'] ?? [];

        if (in_array($toolConfig['language'], ['php', 'javascript', 'typescript', 'python', 'go', 'elixir'])) {
            if (!empty($options)) {
                $commandParts = array_merge([$command], $options, [$filePath]);
            } else {
                $commandParts = [$command, $filePath];
            }
        } else {
            $commandParts = array_merge([$command], $options);
        }

        Log::debug('StaticAnalysisService: Executing command - ' . implode(' ', $commandParts));

        $process = new Process($commandParts);
        $process->setTimeout(300);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            Log::error("StaticAnalysisService: {$toolName} failed for '{$filePath}'. Error: {$exception->getMessage()}");
            return null;
        }

        $output = $process->getOutput();

        if ($toolConfig['output_format'] === 'json') {
            $results = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("StaticAnalysisService: Failed to decode JSON output from {$toolName} for '{$filePath}'. Error: " . json_last_error_msg());
                return null;
            }
        } else {
            $results = $output;
        }

        Log::debug("StaticAnalysisService: {$toolName} results for '{$filePath}': " . ($toolConfig['output_format'] === 'json' ? json_encode($results) : $results));

        $staticAnalysis = StaticAnalysis::create([
            'code_analysis_id' => $codeAnalysis->id,
            'tool' => $toolName,
            'results' => $results,
        ]);

        Log::info("StaticAnalysisService: {$toolName} analysis stored for '{$filePath}'.");

        return $staticAnalysis;
    }
}
