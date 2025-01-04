# Laravel AI Code Analysis Project

![Laravel Logo](https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg)

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Artisan Commands](#artisan-commands)
- [Logging](#logging)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Overview

This Laravel-based project leverages advanced AI capabilities to perform multi-pass code analysis, documentation generation, and refactoring suggestions. Designed to enhance code quality and maintainability, the system automatically analyzes PHP files, generates comprehensive documentation, and provides actionable refactoring advice using OpenAI's powerful language models.

## Features

- **Multi-Pass AI Analysis**
  - **Documentation Generation:** Automatically creates concise and clear documentation from AST data and raw code.
  - **Refactoring Suggestions:** Provides actionable recommendations to improve code structure, adherence to SOLID principles, and maintainability.
  - **AST Insights:** Offers insights based on Abstract Syntax Tree (AST) data to understand code structure and relationships.

- **Code Parsing and Analysis**
  - Collects and parses PHP files to extract classes, methods, functions, and annotations.
  - Stores analysis results in the database for persistent tracking.

- **Artisan Commands**
  - **`code:analyze`:** Analyzes PHP files, gathers AST data, and applies AI-driven multi-pass analysis.
  - **`parse:files`:** Parses configured files/directories to list discovered classes and functions.
  - **`generate:tests`:** Generates PHPUnit test skeletons for discovered classes and methods.
  - **`passes:process`:** Processes AI analysis passes with options for dry-run and verbosity.
  - **`db:backup`:** Backs up the SQLite database.
  - **`db:backup:restore`:** Restores the SQLite database from a backup file.

- **Database Management**
  - Utilizes SQLite for simplicity and ease of use.
  - Provides migration files to set up necessary database tables.

- **Logging with Contextual Information**
  - Implements detailed logging using Laravel's Context facade for enhanced traceability and debugging.

## Requirements

- **PHP:** >= 8.0
- **Composer:** To manage PHP dependencies.
- **Laravel:** Version 11.x
- **SQLite:** For the database.
- **OpenAI API Key:** To enable AI-driven features.

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

   - Copy the example environment file and configure the necessary variables.

     ```bash
     cp .env.example .env
     ```

   - Open `.env` and set your `OPENAI_API_KEY` along with other configurations as needed.

4. **Generate Application Key**

   ```bash
   php artisan key:generate
   ```

5. **Run Migrations**

   ```bash
   php artisan migrate
   ```

## Configuration

### AI Service Configuration

The AI capabilities are configured in `config/ai.php`. This file defines the AI operations, multi-pass analysis settings, and default model configurations.

- **API Credentials**

  ```php
  'openai_api_key' => env('OPENAI_API_KEY'),
  ```

- **Default AI Settings**

  ```php
  'default' => [
      'model'       => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
      'max_tokens'  => env('AI_DEFAULT_MAX_TOKENS', 500),
      'temperature' => env('AI_DEFAULT_TEMPERATURE', 0.5),
      'system_message' => 'You are a helpful AI assistant.',
  ],
  ```

- **AI Operations**

  Define each AI operation with specific configurations.

  ```php
  'operations' => [
      'code_analysis' => [
          'driver'         => 'chat',
          'model'          => env('CODE_ANALYSIS_MODEL', 'gpt-4o-mini'),
          'max_tokens'     => env('CODE_ANALYSIS_MAX_TOKENS', 1500),
          'temperature'    => env('CODE_ANALYSIS_TEMPERATURE', 0.4),
          'system_message' => 'You are an assistant that generates comprehensive documentation from AST data. Focus on describing classes, methods, parameters, and the usage context.',
          'prompt'         => '',
      ],
      // Add additional operations as needed...
  ],
  ```

- **Multi-Pass Analysis**

  Configure the order and specifics of each analysis pass.

  ```php
  'multi_pass_analysis' => [
      'pass_order' => [
          'doc_generation',
          'refactor_suggestions',
          // Additional passes...
      ],
      'doc_generation' => [
          'operation'    => 'code_analysis',
          'type'         => 'both',
          'max_tokens'   => 1000,
          'temperature'  => 0.3,
          'prompt'       => 'Your prompt here...',
      ],
      'refactor_suggestions' => [
          'operation'    => 'code_improvements',
          'type'         => 'raw',
          'max_tokens'   => 1800,
          'temperature'  => 0.6,
          'prompt'       => 'Your prompt here...',
      ],
      // Additional pass definitions...
  ],
  ```

- **Analysis Limits**

  Set limits to control the scope of analysis.

  ```php
  'analysis_limits' => [
      'limit_class'  => env('ANALYSIS_LIMIT_CLASS', 0),
      'limit_method' => env('ANALYSIS_LIMIT_METHOD', 0),
  ],
  ```

### Logging Configuration

Detailed logging is set up in `config/logging.php`. AI operations utilize a dedicated log channel for better traceability.

- **AI Operations Log Channel**

  Add the following to define a dedicated channel:

  ```php
  'ai_operations' => [
      'driver' => 'single',
      'path' => storage_path('logs/ai_operations.log'),
      'level' => env('LOG_AI_LEVEL', 'debug'),
      'replace_placeholders' => true,
      'tap' => [App\Logging\CustomizeAIFormatter::class],
  ],
  ```

## Usage

### Artisan Commands

The project provides several Artisan commands to perform various tasks.

1. **Analyze PHP Code**

   Analyzes PHP files, gathers AST data, and applies AI-driven multi-pass analysis.

   ```bash
   php artisan code:analyze
   ```

   **Options:**

   - `--output-file=`: Specify a JSON file to store the analysis results.
   - `--limit-class=`: Limit the number of PHP files to analyze.
   - `--limit-method=`: Limit the number of methods per class to process.

2. **Parse Files**

   Parses configured files/directories to list discovered classes and functions.

   ```bash
   php artisan parse:files
   ```

   **Options:**

   - `--filter=`: Filter item names.
   - `--output-file=`: Specify a JSON file to export the parsed data.
   - `--limit-class=`: Limit the number of classes to parse.
   - `--limit-method=`: Limit the number of methods per class to parse.

3. **Generate PHPUnit Tests**

   Generates PHPUnit test skeletons for discovered classes and methods.

   ```bash
   php artisan generate:tests
   ```

   **Options:**

   - `--filter=`: Filter classes for which to generate tests.

4. **Process AI Passes**

   Processes the next AI analysis pass for each `CodeAnalysis` record.

   ```bash
   php artisan passes:process
   ```

   **Options:**

   - `--dry-run`: Run the command without persisting any changes.
   - `--verbose`: Display detailed logs.

5. **Backup Database**

   Backs up the SQLite database.

   ```bash
   php artisan db:backup
   ```

   **Options:**

   - `--path=`: Specify a path to store the backup file.

6. **Restore Database**

   Restores the SQLite database from a backup file.

   ```bash
   php artisan db:backup:restore
   ```

   **Options:**

   - `--path=`: Specify the backup file to restore from.

## Logging

The project utilizes Laravel's Context facade to provide rich, contextual logging information. AI operations are logged separately in the `ai_operations.log` file located in the `storage/logs` directory.

### Log Levels

- **INFO:** General operational messages.
- **DEBUG:** Detailed debugging information.
- **ERROR:** Errors encountered during operations.

### Contextual Information

Contextual data such as `file_path`, `current_pass`, and `pass_order` are automatically included in log entries to facilitate easier debugging and traceability.

## Testing

The project includes PHPUnit tests to ensure the reliability of its features.

1. **Run Tests**

   ```bash
   php artisan test
   ```

2. **Test Structure**

   Tests are located in the `tests` directory and extend the base `TestCase` class. You can find feature tests for various commands and services.

## Contributing

Contributions are welcome! Please follow these steps to contribute:

1. **Fork the Repository**

2. **Create a New Branch**

   ```bash
   git checkout -b feature/YourFeatureName
   ```

3. **Make Your Changes**

4. **Run Tests**

   Ensure all tests pass before submitting.

   ```bash
   php artisan test
   ```

5. **Commit Your Changes**

   ```bash
   git commit -m "Add your detailed description here"
   ```

6. **Push to Your Fork**

   ```bash
   git push origin feature/YourFeatureName
   ```

7. **Create a Pull Request**

   Submit a pull request outlining your changes and their purpose.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
