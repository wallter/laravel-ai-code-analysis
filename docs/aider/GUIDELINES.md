# Aider Guidelines

This document outlines best practices for using Aider or other AI tools to maintain a consistent, high-quality Laravel 11 codebase with PHP 8.4+ and PSR-12 standards.

---

## TLDR; Key Guidelines

1. **Language**
   - Always respond in English.

2. **Code Standards**
   - Ensure PHP files start with `<?php`.
   - Use **Laravel Collections** when possible.
   - Properly import all used classes at the top of the file with `use` statements (e.g., `use Illuminate\Support\Facades\File;`).
   - Follow PSR-12 coding style and maintain proper code indentation and bracket placement for functions, classes, and blocks.

3. **Commands**
   - Avoid creating or using `app/Console/Kernel.php`. Laravel 11 auto-discovers commands.

4. **Comments**
   - Include high-quality, explanatory comments (focus on the "why" behind the code).

5. **Testing**
   - Write unit tests or feature tests for all generated code.

6. **Iterative Workflow**
   - Prefer small, incremental edits that fit within token/complexity limits.
   - Plan and execute large refactors iteratively.

7. **Code Quality**
   - Maintain clean, modular, and reusable code.
   - Ensure generated code integrates seamlessly with the Laravel ecosystem.

8. **Documentation**
   - Provide clear and concise inline comments and ensure documentation is generated for any public-facing interfaces or services.

---

These practices ensure Aider-generated code adheres to Laravel 11 and PHP 8.4+ standards while promoting clarity, modularity, and maintainability.