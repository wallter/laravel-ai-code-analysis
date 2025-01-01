# Aider Guidelines

This document is specifically for Aider and other AI tools working within this repository.  
It outlines best practices, constraints, and usage patterns to maintain a consistent, Laravel-based workflow.

---

## 1. Laravel Ecosystem First

- **Migrations**  
  - Always create or modify migrations using Laravel’s built-in commands:
    ```bash
    php artisan make:migration create_parsed_items_table --create=parsed_items
    ```
  - Avoid manually writing raw migration files unless absolutely necessary.  
  - This helps keep the code consistent with Laravel conventions.

- **Commands**  
  - If you need to create or modify Artisan commands, use:
    ```bash
    php artisan make:command MyCommand
    ```
  - This scaffolds a proper command class in `app/Console/Commands/`.  
  - If the command already exists, make sure changes go into the existing class file.

---

## 2. Code Style & Organization

- **Follow PSR-12**: Ensure you adhere to standard PHP coding guidelines (PSR-12) for indentation, braces, namespaces, etc.  
- **Directory Structure**:  
  - **Parsing Logic**: in `app/Services/Parsing/` (e.g. `FunctionAndClassVisitor.php`, `ParserService.php`).  
  - **Database Migrations**: in `database/migrations/`.  
  - **Configs**: in `config/`, such as `config/parsing.php` for file paths, ignore patterns, etc.  
  - **Tests**: in `tests/`, created via `php artisan make:test`.

---

## 3. Database & Models

- **Eloquent Models**  
  - Use `php artisan make:model ParsedItem` to create or modify a model for storing parse results.  
  - Place it in `app/Models/ParsedItem.php` (or a sub-namespace, if desired).
- **Updating Rows**  
  - For repeated scans, rely on `updateOrCreate()` or `firstOrNew()` to avoid duplicate DB entries.

---

## 4. AI/LLM Integration

- **Accuracy Focus**  
  - AST-based parsing should remain the primary source of factual data (e.g., parameters, function references).  
  - LLM suggestions can enhance doc clarity or summaries, but any “factual” changes must be reverified against real code.

---

## 5. Prompt Format & Best Practices

- **Clear Objectives**  
  - When you prompt Aider, specify exactly what changes or additions you want in code.  
  - Reference these guidelines by name if needed, e.g., “Please follow the rules in `docs/aider/GUIDELINES.md`.”

- **Propose Diffs**  
  - For new migrations, request `php artisan make:migration...` commands, or ask Aider to show the resulting migration.  
  - For existing code, request small diffs or well-explained patches so changes can be validated easily.

---

## 6. Testing & Coverage

- **Keep Tests Updated**  
  - If you add or refactor core functionality, ensure existing tests still pass.  
  - Create new tests for any new features (`php artisan make:test SomeFeatureTest`).  
- **Code Coverage**  
  - Focus on critical parsing and storage logic. Use `XDEBUG_MODE=coverage php artisan test --coverage` or equivalent.