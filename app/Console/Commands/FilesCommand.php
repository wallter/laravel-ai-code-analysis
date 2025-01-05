<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Provides shared options: --output-file, --limit-class, --limit-method.
 * Many commands can extend this for consistent option handling.
 */
abstract class FilesCommand extends Command
{
    protected $signature = 'code:base
        {--output-file=}
        {--limit-class=}
        {--limit-method=}';

    protected $description = 'Base command providing common arguments.';

    /**
     * Get the output file option.
     *
     * @return string|null The output file path or null if not set.
     */
    protected function getOutputFile(): ?string
    {
        $file = $this->option('output-file');
        if ($file && ! str_ends_with(strtolower($file), '.json')) {
            $file .= '.json';
        }

        return $file ?: null;
    }

    /**
     * Get the class limit option.
     *
     * @return int The number of classes to limit.
     */
    protected function getClassLimit(): int
    {
        return (int) ($this->option('limit-class') ?: 0);
    }

    /**
     * Get the method limit option.
     *
     * @return int The number of methods to limit per class.
     */
    protected function getMethodLimit(): int
    {
        return (int) ($this->option('limit-method') ?: 0);
    }

    /**
     * Determine if the command is running in verbose mode.
     *
     * @return bool True if verbose, false otherwise.
     */
    protected function isVerbose(): bool
    {
        return (bool) $this->output->isVerbose();
    }
}
