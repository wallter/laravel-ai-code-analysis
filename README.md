Below is an improved README.md that showcases your portfolio project while also being suitable for real-world usage. It includes references to queued AI operations, token usage tracking, and the broader multi-pass analysis approach.

# Laravel AI Code Analysis Project
[![License](https://img.shields.io/badge/license-Apache%202.0-blue.svg)](LICENSE)

## Overview

This Laravel-based project uses **OpenAI’s language models** in tandem with **PHP Abstract Syntax Tree (AST) analysis** (via [nikic/php-parser](https://github.com/nikic/PHP-Parser)) to deliver a **comprehensive multi-pass code analysis**. It iteratively inspects PHP codebases, generating:

- **Automated Documentation** from raw code + AST data
- **Refactoring Suggestions** for clarity and adherence to best practices
- **Functionality Assessments** focusing on performance and maintainability

By leveraging **queued** AI operations, **token usage** tracking, and other advanced features, developers can **enhance maintainability**, **optimize performance**, and **ensure** cleaner, more efficient code.

---

## Table of Contents
- [Laravel AI Code Analysis Project](#laravel-ai-code-analysis-project)
  - [Overview](#overview)
  - [Table of Contents](#table-of-contents)
  - [Features](#features)
    - [Asynchronous (Queued) AI Operations](#asynchronous-queued-ai-operations)
    - [Multi-Pass AI Analysis](#multi-pass-ai-analysis)
    - [Comprehensive AST Parsing](#comprehensive-ast-parsing)
  - [Requirements](#requirements)
  - [Installation](#installation)
- [1) Set up your .env with OPENAI\_API\_KEY, choose model](#1-set-up-your-env-with-openai_api_key-choose-model)
- [2) Migrate DB](#2-migrate-db)
- [3) Set your file/folder scanning in config/parsing.php](#3-set-your-filefolder-scanning-in-configparsingphp)
- [Start the queue if you want asynchronous passes:](#start-the-queue-if-you-want-asynchronous-passes)
- [4) Parse code, store results:](#4-parse-code-store-results)
- [5) Analyze code, queue AI passes:](#5-analyze-code-queue-ai-passes)
- [6) Process additional passes if needed:](#6-process-additional-passes-if-needed)

---

## Features

### Asynchronous (Queued) AI Operations
- **Job Dispatching**: Each analysis pass (e.g., documentation, style review, performance checks) is dispatched as a *queued* job, avoiding blocking CLI or HTTP requests.
- **Progressive Pass Completion**: Allows each pass to run independently, enabling partial or sequential pass execution.
- **Resilient**: Jobs can automatically retry in case of transient failures or AI rate limiting.

### Multi-Pass AI Analysis
- **Documentation Generation**: Creates concise, structured docs from AST data + raw code. Summarizes classes, methods, parameters, and usage context.
- **Refactoring Suggestions**: Highlights areas for improved code structure, maintainability, and performance, adhering to PHP standards and design principles.
- **Style & Convention Review**: Ensures PSR compliance and consistent formatting. Recommends fixes for naming, docblocks, or spacing issues.
- **Functionality / Performance**: Identifies edge cases, memory usage issues, and bottlenecks, providing suggestions for reliability and scalability.
- **Dependency Review**: Locates and audits external libraries for deprecated or insecure packages, recommending modern alternatives or patches.
- **Dependent Passes**: (Planned) New passes can reuse data and suggestions from previous passes to produce consolidated final summaries.

### Comprehensive AST Parsing
- **nikic/php-parser**: Extracts classes, traits, interfaces, methods, line numbers, docblocks, parameters, and more.
- **Detailed Metadata**: Stores discovered items in the database, enabling subsequent analysis passes (and queued jobs).
- **Configurable**: Manage which files/folders to parse via `config/parsing.php`.

---

## Requirements
- **PHP** >= 8.0  
- **Laravel** 10 or 11.x  
- **Composer** (for dependencies)  
- **OpenAI API Key** (ENV: `OPENAI_API_KEY`)  
- **SQLite** (or other DB) for storing analysis data  
- **Queue Driver** (e.g., `redis` or `database`)

---

## Installation

1. **Clone the Repository**  
   ```bash
   git clone https://github.com/your-username/laravel-ai-code-analysis.git
   cd laravel-ai-code-analysis

	2.	Install Dependencies

composer install


	3.	Set Up Environment Variables

cp .env.example .env
php artisan key:generate

	•	Add your OPENAI_API_KEY and any other settings.

	4.	Run Migrations

php artisan migrate


	5.	Configure Queues
	•	If you plan to run AI passes asynchronously, ensure QUEUE_CONNECTION is set (e.g., database, redis) and run a worker:

php artisan queue:work

Configuration

AI Service Configuration

Located in config/ai.php. Defines:
	•	Default Model & Tokens (AI_DEFAULT_MODEL, etc.)
	•	Operations (e.g., code_analysis, doc_generation, style_review)
	•	Multi-Pass Analysis specifying pass order and prompts.

Example:

'multi_pass_analysis' => [
    'pass_order' => [
        'doc_generation',
        'functional_analysis',
        'style_convention',
        'consolidation_pass',
    ],
    // Pass details: operation, type (ast|raw|both|previous), max_tokens, temperature, etc.
],

Parsing Configuration

In config/parsing.php, define:
	•	Folders to scan (recursively) for .php files
	•	Specific .php files to parse
	•	The ParserService will gather AST data from these paths.

Usage

Artisan Commands
	1.	Parse Files

php artisan parse:files --output-file=docs/parse_all.json --verbose

	•	Collects PHP files, stores discovered items (classes, functions) in the DB (via ParsedItem or similar).

	2.	Analyze Files (Multi-Pass)

php artisan analyze:files --output-file=docs/analyze_all.json --verbose

	•	Creates or updates a CodeAnalysis record for each file.
	•	Queues AI passes if using the new asynchronous approach.

	3.	Process Passes

php artisan passes:process

	•	Finds any CodeAnalysis needing further passes and dispatches them to the queue.
	•	Use --dry-run to test logic without storing AI results, --verbose for extra logs.

	4.	Generate Tests (experimental)

php artisan generate:tests

	•	Creates or updates test files for discovered classes & methods (in progress).

	5.	DB Backup / Restore

php artisan db:backup
php artisan db:backup:restore

	•	Backup or restore the SQLite DB as needed.

Token & Cost Tracking
	•	OpenAIService captures usage stats (prompt_tokens, completion_tokens, total_tokens) per request.
	•	AIResult stores the usage in metadata->usage.
	•	If desired, you can compute a cost estimate in USD by applying your own rate (e.g., $0.002 per 1K tokens).

(See ProcessAnalysisPassJob or your service logic for examples.)

Queued Analysis
	•	Multi-pass analysis (e.g., doc generation, performance, style, etc.) is queued via ProcessAnalysisPassJob.
	•	This prevents blocking the main process and improves reliability (retries on fail).
	•	Ensure you have a queue worker running:

php artisan queue:work


	•	Once completed, results are in ai_results table.

Testing
	•	Run Tests:

php artisan test


	•	Coverage: Some tests focus on AST parsing or command execution.
	•	CI: Integrate into GitHub Actions for continuous testing.

Contributing

We welcome contributions! Common steps:
	1.	Fork & branch off main.
	2.	Make changes, add tests (where feasible).
	3.	Submit a pull request describing your improvements.

License

Licensed under the Apache 2.0 License. You’re free to use and modify this project for commercial or personal use as long as you maintain the license. See the LICENSE file for more details.

Usage TLDR

# 1) Set up your .env with OPENAI_API_KEY, choose model
cp .env.example .env
php artisan key:generate

# 2) Migrate DB
php artisan migrate

# 3) Set your file/folder scanning in config/parsing.php
#    Start the queue if you want asynchronous passes:
php artisan queue:work

# 4) Parse code, store results:
php artisan parse:files --output-file=docs/parse_all.json --verbose

# 5) Analyze code, queue AI passes:
php artisan analyze:files --output-file=docs/analyze_all.json --verbose

# 6) Process additional passes if needed:
php artisan passes:process --verbose

For more details on advanced usage, consult the Configuration and Artisan Commands sections.