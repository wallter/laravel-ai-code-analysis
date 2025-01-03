# Project Overview for Aider

This document provides Aider (and any other AI tools) with context about the entire project’s goals and current functionality. Refer to this overview before making major changes or adding features.

---

## 1. Purpose

We are building an **automated documentation and code-analysis system** for a **Laravel** app. The system will:
1. Enhance documentation and code understanding by storing and utilizing Abstract Syntax Trees (ASTs).
2. Store parse results in a database for queries, refactoring assistance, or doc generation.
3. Leverage AI services to expand or refine documentation using detailed context from ASTs.
4. Provide Artisan commands for core operations (e.g., `parse:files`, `generate:tests`).