<?php

namespace App\Enums;

/**
 * Enum OperationIdentifier
 *
 * Defines operation identifiers for AI passes.
 */
enum OperationIdentifier: string
{
    case DOC_GENERATION = 'doc_generation';
    case FUNCTIONAL_ANALYSIS = 'functional_analysis';
    case STYLE_CONVENTION = 'style_convention';
    case CONSOLIDATION_PASS = 'consolidation_pass';
    case SCORING_PASS = 'scoring_pass';
    case LARAVEL_MIGRATION = 'laravel_migration';
    case LARAVEL_MIGRATION_SCORING = 'laravel_migration_scoring';
    case STATIC_ANALYSIS = 'static_analysis';
}
