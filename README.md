# Laravel AI Code Analysis Project
[![License](https://img.shields.io/badge/license-Apache%202.0-blue.svg)](LICENSE)

## Overview

This Laravel-based project leverages **OpenAI’s language models** in combination with **PHP Abstract Syntax Tree (AST) analysis** (using [nikic/php-parser](https://github.com/nikic/PHP-Parser)) to deliver a **comprehensive multi-pass code analysis**. It systematically inspects PHP codebases, generating:

- **Automated Documentation** from raw code and AST data
- **Refactoring Suggestions** to enhance clarity and adhere to best practices
- **Functionality Assessments** focusing on performance and maintainability

By utilizing **queued AI operations**, **token usage tracking**, and other advanced features, developers can **enhance maintainability**, **optimize performance**, and ensure cleaner, more efficient code.

---

## Table of Contents
- [Laravel AI Code Analysis Project](#laravel-ai-code-analysis-project)
  - [Overview](#overview)
  - [Features](#features)
    - [Asynchronous (Queued) AI Operations](#asynchronous-queued-ai-operations)
    - [Multi-Pass AI Analysis](#multi-pass-ai-analysis)
    - [Comprehensive AST Parsing](#comprehensive-ast-parsing)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Usage](#usage)
    - [Artisan Commands](#artisan-commands)
    - [Token & Cost Tracking](#token--cost-tracking)
    - [Queued Analysis](#queued-analysis)
    - [Testing](#testing)
  - [Contributing](#contributing)
  - [License](#license)

---

## Features

### Asynchronous (Queued) AI Operations
- **Job Dispatching**: Each analysis pass (e.g., documentation, style review, performance checks) is dispatched as a *queued* job, preventing blocking of CLI or HTTP requests.
- **Progressive Pass Completion**: Allows each pass to run independently, enabling partial or sequential pass execution.
- **Resilient**: Jobs can automatically retry in case of transient failures or AI rate limiting.

### Multi-Pass AI Analysis
- **Documentation Generation**: Creates concise, structured docs from AST data and raw code. Summarizes classes, methods, parameters, and usage context.
- **Refactoring Suggestions**: Highlights areas for improved code structure, maintainability, and performance, adhering to PHP standards and design principles.
- **Style & Convention Review**: Ensures PSR compliance and consistent formatting. Recommends fixes for naming, docblocks, or spacing issues.
- **Functionality / Performance**: Identifies edge cases, memory usage issues, and bottlenecks, providing suggestions for reliability and scalability.
- **Dependency Review**: Locates and audits external libraries for deprecated or insecure packages, recommending modern alternatives or patches.
- **Dependent Passes**: *(Planned)* New passes can reuse data and suggestions from previous passes to produce consolidated final summaries.

### Comprehensive AST Parsing
- **nikic/php-parser**: Extracts classes, traits, interfaces, methods, line numbers, docblocks, parameters, and more.
- **Detailed Metadata**: Stores discovered items in the database, enabling subsequent analysis passes and queued jobs.
- **Configurable**: Manage which files/folders to parse via `config/parsing.php`.

---

## Requirements
- **PHP** >= 8.0  
- **Laravel** 10 or 11.x  
- **Composer** (for dependencies)  
- **OpenAI API Key** (ENV: `OPENAI_API_KEY`)  
- **SQLite** (or another supported database) for storing analysis data  
- **Queue Driver** (e.g., `redis` or `database`)

---

## Installation

1. **Clone the Repository**  
   ```bash
   git clone https://github.com/your-username/laravel-ai-code-analysis.git
   cd laravel-ai-code-analysis
   ```

2. **Install Dependencies**  
   ```bash
   composer install
   ```

3. **Set Up Environment Variables**  
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   - Add your `OPENAI_API_KEY` and any other necessary settings to the `.env` file.

4. **Run Migrations**  
   ```bash
   php artisan migrate
   ```

5. **Configure Queues**
   - If you plan to run AI passes asynchronously, ensure `QUEUE_CONNECTION` is set (e.g., `database`, `redis`) in your `.env` file.
   - Start a queue worker:
     ```bash
     php artisan queue:work
     ```

---

## Usage

### Artisan Commands

1. **Parse Files**  
   Collects PHP files and stores discovered items (classes, functions) in the database.
   ```bash
   php artisan parse:files --output-file=docs/parse_all.json --verbose
   ```

2. **Analyze Files (Multi-Pass)**  
   Creates or updates a `CodeAnalysis` record for each file and queues AI passes if using the asynchronous approach.
   ```bash
   php artisan analyze:files --output-file=docs/analyze_all.json --verbose
   ```

3. **Process Passes**  
   Finds any `CodeAnalysis` needing further passes and dispatches them to the queue.
   ```bash
   php artisan passes:process
   ```
   - Use `--dry-run` to test logic without storing AI results.
   - Use `--verbose` for extra logs.

4. **Generate Tests (Experimental)**  
   Creates or updates test files for discovered classes and methods.
   ```bash
   php artisan generate:tests
   ```

5. **DB Backup / Restore**  
   Backup or restore the SQLite DB as needed.
   ```bash
   php artisan db:backup
   php artisan db:backup:restore
   ```

### Token & Cost Tracking
- **OpenAIService** captures usage stats (`prompt_tokens`, `completion_tokens`, `total_tokens`) per request.
- **AIResult** stores the usage in `metadata->usage`.
- Compute a cost estimate in USD by applying your own rate (e.g., $0.002 per 1K tokens).

*(See `ProcessAnalysisPassJob` or your service logic for examples.)*

### Queued Analysis
- **Multi-Pass Analysis** (e.g., doc generation, performance, style) is queued via `ProcessAnalysisPassJob`.
- Prevents blocking the main process and improves reliability with retries on failure.
- Ensure you have a queue worker running:
  ```bash
  php artisan queue:work
  ```
- Once completed, results are stored in the `ai_results` table.

### Testing
- **Run Tests**  
  ```bash
  php artisan test
  ```
- **Coverage**: Focuses on AST parsing and command execution.
- **CI**: Integrate into GitHub Actions for continuous testing.

---

## Contributing

We welcome contributions! Common steps:
1. Fork the repository and create a new branch from `main`.
2. Make your changes and add tests where feasible.
3. Submit a pull request describing your improvements.

---

## License

Licensed under the Apache 2.0 License. You’re free to use and modify this project for commercial or personal use as long as you maintain the license. See the [LICENSE](LICENSE) file for more details.

---

## Usage TLDR

