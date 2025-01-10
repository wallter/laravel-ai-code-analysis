<?php

namespace Tests\Unit\Services\Parsing;

use App\Services\ParsedItemService;
use App\Services\Parsing\ParserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;

class ParserServiceTest extends TestCase
{
    /**
     * @var ParserService
     */
    protected $parserService;

    /**
     * @var MockInterface
     */
    protected $parsedItemServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parsedItemServiceMock = \Mockery::mock(ParsedItemService::class);
        $this->parserService = new ParserService($this->parsedItemServiceMock);
    }

    public function test_collect_php_files_includes_parsing_files()
    {
        // Arrange
        Config::shouldReceive('get')
            ->with('parsing.files', [])
            ->andReturn([
                base_path('app/Services/AI/CodeAnalysisService.php'),
                base_path('app/Example.php'), // Existing file
            ]);

        Config::shouldReceive('get')
            ->with('parsing.folders', [])
            ->andReturn([
                base_path('app/Services'),
            ]);

        // Mock File existence
        File::shouldReceive('allFiles')
            ->with(base_path('app/Services'))
            ->andReturn(collect([
                new \Illuminate\Filesystem\FilesystemIterator(base_path('app/Services/AI/CodeAnalysisService.php')),
                new \Illuminate\Filesystem\FilesystemIterator(base_path('app/Services/SomeOtherService.php')),
            ]));

        // Mock realpath for files
        $this->parsedItemServiceMock->shouldReceive('createParsedItem')->andReturnNull();

        // Mock File::get
        File::shouldReceive('get')->andReturn('<?php // PHP Code');

        // Act
        $phpFiles = $this->parserService->collectPhpFiles();

        // Assert
        $this->assertInstanceOf(Collection::class, $phpFiles);
        $this->assertCount(2, $phpFiles);
        $this->assertTrue($phpFiles->contains(base_path('app/Services/AI/CodeAnalysisService.php')));
        $this->assertTrue($phpFiles->contains(base_path('app/Services/SomeOtherService.php')));
    }

    public function test_parse_file_throws_exception_for_invalid_path()
    {
        // Arrange
        $invalidPath = 'app/NonExistent.php';

        Config::shouldReceive('get')
            ->with('filesystems.base_path')
            ->andReturn(base_path());

        File::shouldReceive('get')
            ->with(base_path(DIRECTORY_SEPARATOR.$invalidPath))
            ->andThrow(new \Exception('File not found'));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn ($message) => str_contains((string) $message, 'Failed to read'));

        // Act
        $ast = $this->parserService->parseFile($invalidPath);

        // Assert
        $this->assertEmpty($ast);
    }

    public function test_parse_file_returns_ast_for_valid_file()
    {
        // Arrange
        $validPath = 'app/Services/AI/CodeAnalysisService.php';

        Config::shouldReceive('get')
            ->with('filesystems.base_path')
            ->andReturn(base_path());

        $code = '<?php class TestClass {}';

        File::shouldReceive('get')
            ->with(base_path(DIRECTORY_SEPARATOR.$validPath))
            ->andReturn($code);

        $parserMock = \Mockery::mock(\PhpParser\Parser::class);
        $parserMock->shouldReceive('parse')
            ->with($code)
            ->andReturn(['ast_node']);

        $this->app->instance(\PhpParser\ParserFactory::class, fn () => new class($parserMock) extends \PhpParser\ParserFactory
        {
            public function __construct(private $parser) {}

            public function createForNewestSupportedVersion()
            {
                return $this->parser;
            }
        });

        $this->parsedItemServiceMock->shouldReceive('createParsedItem')->times(1)->andReturnNull();

        // Act
        $ast = $this->parserService->parseFile($validPath);

        // Assert
        $this->assertIsArray($ast);
        $this->assertEquals(['ast_node'], $ast);
    }

    public function test_collect_php_files_throws_exception_for_invalid_files()
    {
        // Arrange
        Config::shouldReceive('get')
            ->with('parsing.files', [])
            ->andReturn([
                base_path('app/NonExistent.php'),
            ]);

        Config::shouldReceive('get')
            ->with('parsing.folders', [])
            ->andReturn([]);

        // Act
        $phpFiles = $this->parserService->collectPhpFiles();

        // Assert
        $this->assertInstanceOf(Collection::class, $phpFiles);
        $this->assertCount(0, $phpFiles);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
