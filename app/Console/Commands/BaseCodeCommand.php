<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * A base command that provides common options:
 *  --output-file
 *  --limit-class
 *  --limit-method
 * 
 * It also provides helper methods to parse these options, 
 * ensuring we unify the logic for both child commands.
 */
abstract class BaseCodeCommand extends Command
{
    /**
     * We define a "generic" signature to hold the shared options.
     * Each child command will override the actual command name but can reuse these options.
     */
    protected $signature = 'code:base
        {--output-file= : If set, where to write JSON output (a .json file).}
        {--limit-class= : Limit the number of classes processed.}
        {--limit-method= : Limit the number of methods per class.}';

    protected $description = 'Base command for code operations.';

    /**
     * Our standard handle() simply calls the child's executeCommand(). 
     */
    public function handle(): int
    {
        return $this->executeCommand();
    }

    /**
     * Child classes override this to implement actual logic.
     */
    abstract protected function executeCommand(): int;

    /**
     * Get or transform the --output-file option, forcing ".json" if set.
     */
    protected function getOutputFile(): ?string
    {
        $file = $this->option('output-file') ?: null;
        if ($file && !str_ends_with(strtolower($file), '.json')) {
            $file .= '.json';
        }
        return $file;
    }

    /**
     * Retrieve the --limit-class option as integer.
     */
    protected function getClassLimit(): int
    {
        return (int) ($this->option('limit-class') ?: 0);
    }

    /**
     * Retrieve the --limit-method option as integer.
     */
    protected function getMethodLimit(): int
    {
        return (int) ($this->option('limit-method') ?: 0);
    }

    /**
     * Determine if the command is running in verbose mode.
     *
     * @return bool
     */
    protected function isVerbose(): bool
    {
        return $this->getOutput()->isVerbose();
    }
}
