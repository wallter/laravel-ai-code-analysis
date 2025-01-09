<?php

namespace App\Services;

use App\Models\CodeAnalysis;
use App\Models\StaticAnalysis;
use App\Services\StaticAnalysis\StaticAnalysisToolInterface;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Exception;

class StaticAnalysisService implements StaticAnalysisToolInterface
{
    /**
     * Run static analysis on the given file and store results.
     *
     * @param CodeAnalysis $codeAnalysis
     * @return StaticAnalysis|null
     */
    /**
     * Run static analysis on the given file using the specified tool and store results.
     *
     * @param CodeAnalysis $codeAnalysis
     * @param string $toolName
     * @return StaticAnalysis|null
     */
    public function runAnalysis(CodeAnalysis $codeAnalysis, string $toolName): ?StaticAnalysis
    {
        $filePath = $codeAnalysis->file_path;
        Log::info("StaticAnalysisService: Running {$toolName} on '{$filePath}'.");

        switch ($toolName) {
            case 'PHPStan':
                $command = ['vendor/bin/phpstan', 'analyse', $filePath, '--json'];
                break;
            case 'PHP_CodeSniffer':
                $command = ['vendor/bin/phpcs', '--report=json', $filePath];
                break;
            case 'Psalm':
                $command = ['vendor/bin/psalm', '--output-format=json', $filePath];
                break;
            default:
                Log::error("StaticAnalysisService: Unsupported static analysis tool '{$toolName}'.");
                return null;
        }

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            Log::error("StaticAnalysisService: {$toolName} failed for '{$filePath}'. Error: {$errorOutput}");
            return null;
        }

        $output = $process->getOutput();
        $results = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("StaticAnalysisService: Failed to decode JSON output from {$toolName} for '{$filePath}'.");
            return null;
        }

        $staticAnalysis = StaticAnalysis::create([
            'code_analysis_id' => $codeAnalysis->id,
            'tool' => $toolName,
            'results' => $results,
        ]);

        Log::info("StaticAnalysisService: {$toolName} analysis stored for '{$filePath}'.");

        return $staticAnalysis;
    }
}
