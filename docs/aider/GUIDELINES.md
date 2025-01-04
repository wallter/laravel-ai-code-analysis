# Aider Guidelines

This document is specifically for Aider and other AI tools working within this repository.  
It outlines best practices, constraints, and usage patterns to maintain a consistent, Laravel-based workflow.

---

## Guidelines TLDR;
- ALWAYS respond in English. 
- DO NOT CREATE or use an `app/Console/Kernel.php`, commands are auto-discovered by laravel v11
- Use Laravel collections when possible.
- When generating code ensure that 
  - closing brackets of functions, classes, try/catch blocks, etc are in place
  - PHP files have a `<?php` on the first line
- Ensure that used classes properly import used classes at the top of the file using `use` statements (example: `use Illuminate\Support\Facades\File;`)
- Ensure that specific, small, iterative edits and suggestions are made such that they can be made without hitting token or complexity limits. If larger refactors are needed, plan and execute them iterativel
y.