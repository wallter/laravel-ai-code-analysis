<?php

namespace App\Services;

use App\Models\CodeAnalysis;
use App\Models\StaticAnalysis;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use App\Services\StaticAnalysis\StaticAnalysisToolInterface;

class StaticAnalysisService implements StaticAnalysisToolInterface
{
    /**
     * Run static analysis on the given file and store results.
     *
     * @param CodeAnalysis $codeAnalysis
     * @return StaticAnalysis|null
     */
    public function runAnalysis(CodeAnalysis $codeAnalysis): ?StaticAnalysis
    {
        $filePath = $codeAnalysis->file_path;
        Log::info("StaticAnalysisService: Running PHPStan on '{$filePath}'.");

        // Define the PHPStan command
        $process = new Process(['vendor/bin/phpstan', 'analyse', $filePath, '--json']);
        $process->run();

        // Check if the process was successful
        if (!$process->isSuccessful()) {
            Log::error("StaticAnalysisService: PHPStan failed for '{$filePath}'. Error: " . $process->getErrorOutput());
            return null;
            // Optionally, throw an exception
            // throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        $results = json_decode($output, true);

        // Store the results in the database
        $staticAnalysis = StaticAnalysis::create([
            'code_analysis_id' => $codeAnalysis->id,
            'tool' => 'PHPStan',
            'results' => $results,
        ]);

        Log::info("StaticAnalysisService: PHPStan analysis stored for '{$filePath}'.");

        return $staticAnalysis;
    }
}
