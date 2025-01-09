<?php

namespace App\Services\StaticAnalysis;

use App\Models\CodeAnalysis;
use App\Models\StaticAnalysis;

/**
 * Interface for static analysis tools.
 */
interface StaticAnalysisToolInterface
{
    /**
     * Run static analysis on the given CodeAnalysis entry using the specified tool and store results.
     */
    public function runAnalysis(CodeAnalysis $codeAnalysis, string $toolName): ?StaticAnalysis;
}
