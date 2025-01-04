# Laravel AI Code Analysis Project
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)

## Overview

This Laravel-based project leverages **advanced AI** to deliver comprehensive multi-pass code analysis, combining automated documentation generation, refactoring suggestions, and functionality assessments. By integrating **OpenAI’s language models** with **PHP Abstract Syntax Tree (AST)** analysis via [nikic/php-parser](https://github.com/nikic/PHP-Parser), the system iteratively analyzes PHP codebases, transforming raw code into actionable insights. It documents systems, enhances maintainability, optimizes performance, and ensures adherence to best practices, empowering developers to create cleaner, more efficient, and robust code.

## Table of Contents

- [Laravel AI Code Analysis Project](#laravel-ai-code-analysis-project)
  - [Overview](#overview)
  - [Table of Contents](#table-of-contents)
  - [Usage TLDR](#usage-tldr)
  - [Features](#features)
    - [Code Parsing and Analysis](#code-parsing-and-analysis)
    - [Multi-Pass AI Analysis](#multi-pass-ai-analysis)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Configuration](#configuration)
    - [AI Service Configuration](#ai-service-configuration)
    - [Parsing Configuration](#parsing-configuration)
  - [Usage](#usage)
    - [Artisan Commands](#artisan-commands)
  - [Testing](#testing)
  - [Contributing](#contributing)
  - [License](#license)

## Usage TLDR

1. **Set up your `.env` with `OPENAI_API_KEY`, choose model**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

2. **Migrate DB**

    ```bash
    php artisan migrate
    ```

3. **Set your file/folder scanning in `config/parsing.php`**

    **Start the queue if you want asynchronous passes:**

    ```bash
    php artisan queue:work
    ```

4. **Parse code, store results:**

    ```bash
    php artisan parse:files --output-file=docs/parse_all.json --verbose
    ```

5. **Analyze code, queue AI passes:**

    ```bash
    php artisan analyze:files --output-file=docs/analyze_all.json --verbose
    ```

6. **Process additional passes if needed:**

    ```bash
    php artisan passes:process --verbose
    ```

## Features

### Code Parsing and Analysis

...

## Requirements

...

## Installation

...

## Configuration

### AI Service Configuration

...

### Parsing Configuration

...

## Usage

### Artisan Commands

...

## Testing

...

## Contributing

...

## License

...
