<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ScriptRunnerService
{
    public function runScript(string $command, array $arguments = [], ?int $timeout = null): ?string
    {
        $commandParts = array_merge([$command], $arguments);
        Log::debug('ScriptRunnerService: Executing command - ' . implode(' ', $commandParts));

        $process = new Process($commandParts);
        $process->setTimeout($timeout ?? 300);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            Log::error("ScriptRunnerService: Command '{$command}' failed. Error: {$exception->getMessage()}");
            return null;
        }

        $output = $process->getOutput();
        Log::debug("ScriptRunnerService: Command '{$command}' output: " . $output);

        return $output;
    }
}
