<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * BaseCodeCommand
 *  - Provides shared options: --output-file, --limit-class, --limit-method
 *  - Many commands can extend this for consistent option handling
 */
abstract class BaseCodeCommand extends Command
{
    protected $signature = 'code:base
        {--output-file=}
        {--limit-class=}
        {--limit-method=}';

    protected $description = 'Base command providing common arguments.';

    public function handle(): int
    {
        return $this->executeCommand();
    }

    abstract protected function executeCommand(): int;

    protected function getOutputFile(): ?string
    {
        $file = $this->option('output-file');
        if ($file && !str_ends_with(strtolower($file), '.json')) {
            $file .= '.json';
        }
        return $file ?: null;
    }

    protected function getClassLimit(): int
    {
        return (int) ($this->option('limit-class') ?: 0);
    }

    protected function getMethodLimit(): int
    {
        return (int) ($this->option('limit-method') ?: 0);
    }
}