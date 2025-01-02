# Project Overview for Aider

This document provides Aider (and any other AI tools) with context about the entire project’s goals and current functionality. Refer to this overview before making major changes or adding features.

---

## 1. Purpose

We are building an **automated documentation and code-analysis system** for a **Laravel** app. The system will:
1. Enhance documentation and code understanding by storing and utilizing Abstract Syntax Trees (ASTs).
2. Store parse results in a database for queries, refactoring assistance, or doc generation.
3. Leverage AI services to expand or refine documentation using detailed context from ASTs.
4. Provide Artisan commands for core operations (e.g., `parse:files`, `generate:tests`).

---

## 2. Current Status

1. **parse:files**
   - An Artisan command that scans directories or specific file paths, parsing code with `nikic/php-parser`.
   - Collects and stores the AST of functions and methods in the `parsed_items` table.
   - Displays discovered classes/functions in a console table or writes them to JSON.
   - Implements `collectMethodData` in `FunctionAndClassVisitor` to gather serialized ASTs, operation summaries, and called methods.
   - Handles large ASTs by generating warnings when the AST size exceeds a specified limit.

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
   - Enhance the `doc:enhance` command to utilize additional context from ASTs for more accurate documentation.
   - Integrate advanced AI services for deeper code analysis and understanding.

3. **Incremental / Partial Updates**
   - Instead of re-parsing the entire codebase each time, parse changed files only.
   - Use `updateOrCreate()` to keep the DB in sync with real code.

4. **Refactoring & Divergence Checks**
   - Detect code changes that break existing docs or tests, and highlight them for dev attention.
   - Enable an `AIRefactorCommand` for safe, partial code modifications with human review.

5. **Future Enhancements**
   - Implement features like refactoring assistance, code optimization suggestions, and automated testing improvements based on stored ASTs.

---

## 4. Usage Patterns

- **Development**
  - Devs can run `php artisan parse:files` to update or view code parse data.
  - Use `--filter`, `--output-file`, or `--limit-class=N` options for specialized output.
  - Handle large ASTs with warnings displayed when the AST size exceeds the limit.
- **Automated**
  - In CI pipelines or scheduled tasks to keep the DB and docs up to date.
  - Possibly used to generate or update test classes automatically.
- **Utilization**
  - Use stored ASTs and additional data in other commands or services to improve code analysis and documentation.

---

## 5. Best Practices

- Always rely on AST-based facts for real code data (e.g., parameter types).
- Use LLM expansions only after verifying correctness.
- Manage the size of stored ASTs to prevent performance issues.
- Handle warnings related to large ASTs by refactoring code to reduce complexity.
- Keep the `parsed_items` table updated and consistent with the codebase.
- Keep parse logic minimal in commands; prefer services in `app/Services/Parsing/`.
- Follow the guidelines in `docs/aider/GUIDELINES.md` for code style, migrations, testing, etc.
