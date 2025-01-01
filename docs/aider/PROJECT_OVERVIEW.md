# Project Overview for Aider

This document provides Aider (and any other AI tools) with context about the entire project’s goals and current functionality. Refer to this overview before making major changes or adding features.

---

## 1. Purpose

We are building an **automated documentation and code-analysis system** for a **Laravel** app. The system will:
1. Parse PHP code (via AST) to discover classes, functions, parameters, etc.
2. Store parse results in a database for queries, refactoring assistance, or doc generation.
3. Optionally leverage AI (LLMs) to expand or refine documentation beyond basic docblocks.
4. Provide Artisan commands for core operations (e.g., `parse:files`, `generate:tests`).

---

## 2. Current Status

1. **parse:files**  
   - An Artisan command that scans directories or specific file paths, parsing code with `nikic/php-parser`.  
   - Displays discovered classes/functions in a console table or writes them to JSON.  
   - The underlying logic is in `app/Services/Parsing/*`.

2. **generate:tests**  
   - Another command that uses parsed data to scaffold PHPUnit test classes.  
   - Currently in development; we plan to store parse data in a DB so we don’t have to re-parse everything constantly.

3. **Migrations & Database**  
   - We plan to create a table (e.g. `parsed_items`) to store discovered classes/functions.  
   - Future expansions might include relationships, usage stats, or doc expansions.

---

## 3. Roadmap

1. **Database Integration**  
   - Write a migration (using `php artisan make:migration`) for `parsed_items`.  
   - Use an Eloquent model (e.g., `ParsedItem`) for storing/updating parse results.

2. **Documentation Refinement**  
   - Potentially call an AI to rewrite or enhance docs.  
   - Provide a `doc:enhance` or `doc:sync` command that updates the DB with improved text.

3. **Incremental / Partial Updates**  
   - Instead of re-parsing the entire codebase each time, parse changed files only.  
   - Use `updateOrCreate()` to keep the DB in sync with real code.

4. **Refactoring & Divergence Checks**  
   - In future, detect code changes that break existing docs or tests, and highlight them for dev attention.  
   - Possibly enable an `AIRefactorCommand` for safe, partial code modifications with human review.

---

## 4. Usage Patterns

- **Development**  
  - Devs can run `php artisan parse:files` to update or view code parse data.  
  - `--filter` or `--output-file` options provide specialized output.  
- **Automated**  
  - In CI pipelines or scheduled tasks to keep the DB and docs up to date.  
  - Possibly used to generate or update test classes automatically.

---

## 5. Best Practices

- Always rely on AST-based facts for real code data (e.g., parameter types).
- Use LLM expansions only after verifying correctness.  
- Keep parse logic minimal in commands; prefer services in `app/Services/Parsing/`.
- Follow the guidelines in `docs/aider/GUIDELINES.md` for code style, migrations, testing, etc.