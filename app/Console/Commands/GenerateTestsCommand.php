<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use App\Services\Parsing\ClassVisitor;
use Illuminate\Support\Facades\File;

/**
 * Generates PHPUnit test skeletons for discovered classes.
 */
class GenerateTestsCommand extends Command
{
    protected $signature = 'generate:tests {--filter=}';
    protected $description = 'Generates PHPUnit test skeletons for discovered classes and methods';

    public function handle()
    {
        // 1) Grab default paths from Laravel config
        $paths = config('parsing.files', []);

        $filter = $this->option('filter');

        // 2) Setup parser & visitor
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        
        $traverser = new NodeTraverser();
        $visitor = new ClassVisitor();
        $traverser->addVisitor($visitor);

        // 3) Parse files
        foreach ($paths as $path) {
            $realPath = $this->normalizePath($path);
            if (!File::exists($realPath)) {
                $this->warn("Path not found: {$realPath}");
                continue;
            }

            if (File::isDirectory($realPath)) {
                $phpFiles = $this->getPhpFiles($realPath);
                foreach ($phpFiles as $phpFile) {
                    $this->parseOneFile($phpFile, $parser, $traverser, $visitor);
                }
            } else {
                $this->parseOneFile($realPath, $parser, $traverser, $visitor);
            }
        }

        $classes = $visitor->getClasses();

        // 4) Optional filter
        if ($filter) {
            $classes = array_filter($classes, function($class) use ($filter) {
                return stripos($class['name'], $filter) !== false;
            });
        }

        if (empty($classes)) {
            $this->info('No classes found for test generation.');
            return 0;
        }

        // 5) Generate test classes
        foreach ($classes as $class) {
            $this->generateTestClass($class);
        }

        $this->info('Test classes generated/updated successfully.');
        return 0;
    }

    /**
     * Common logic to parse a single file with parser/traverser/visitor.
     */
    private function parseOneFile(string $filePath, $parser, $traverser, $visitor)
    {
        try {
            $code = File::get($filePath);
            $ast  = $parser->parse($code);
            if ($ast === null) {
                throw new \Exception("Unable to parse AST for {$filePath}.");
            }
            $visitor->setCurrentFile($filePath);
            $traverser->traverse($ast);
        } catch (Error $e) {
            $this->error("Parse error in {$filePath}: {$e->getMessage()}");
        } catch (\Exception $e) {
            $this->error("Error processing {$filePath}: {$e->getMessage()}");
        }
    }

    /**
     * Recursively retrieve PHP files in a directory.
     */
    private function getPhpFiles(string $directory): array
    {
        return File::allFiles($directory)
            ->filter(fn($file) => strtolower($file->getExtension()) === 'php')
            ->map(fn($file) => $file->getRealPath())
            ->toArray();
    }

    /**
     * Convert a relative path to absolute if necessary.
     */
    private function normalizePath(string $path): string
    {
        return $this->isAbsolutePath($path) ? $path : base_path($path);
    }

    /**
     * Determines if a given path is absolute.
     *
     * @param string $path
     * @return bool
     */
    private function isAbsolutePath(string $path): bool
    {
        // Check for Unix-like absolute path
        if (strpos($path, '/') === 0) {
            return true;
        }

        // Check for Windows absolute path (e.g., C:\)
        if (preg_match('/^[A-Z]:\\\\/', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Generate or update a PHPUnit test class for the discovered class info.
     */
    private function generateTestClass(array $class)
    {
        $testNamespace = 'Tests\\' . $class['namespace'];
        $testClassName = $class['name'] . 'Test';
        $targetDir     = base_path('tests/' . str_replace('\\', '/', $class['namespace']));
        $targetFile    = "{$targetDir}/{$testClassName}.php";

        // Ensure the directory exists
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0777, true);
        }

        // Create or update test file
        if (!File::exists($targetFile)) {
            $this->createNewTestFile($targetFile, $testNamespace, $testClassName, $class);
            $this->info("Created new test class at {$targetFile}");
        } else {
            // Append missing test methods if necessary
            $this->appendMissingMethods($targetFile, $class);
        }
    }

    /**
     * Creates a new PHPUnit test file with a basic template.
     */
    private function createNewTestFile(string $path, string $namespace, string $testClassName, array $classInfo)
    {
        $contents = <<<PHP
<?php

namespace {$namespace};

use PHPUnit\Framework\TestCase;
use {$classInfo['fullyQualifiedName']};

/**
 * Class {$testClassName}
 * @covers {$classInfo['fullyQualifiedName']}
 */
class {$testClassName} extends TestCase
{
    // TODO: Add setUp() if needed for mocks

    public function testExample()
    {
        \$this->markTestIncomplete('Implement tests for class: {$classInfo['name']}');
    }
}
PHP;
        File::put($path, $contents);
    }

    /**
     * Appends missing test methods to an existing test file.
     */
    private function appendMissingMethods(string $path, array $classInfo)
    {
        // Example: Read file content, parse, add missing test methods
        // This is a placeholder for actual implementation.
        $this->info("Updated existing test file: {$path} with new methods (Not Implemented).");
    }
}
