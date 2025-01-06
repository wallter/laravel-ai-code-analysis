<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\CodeAnalysisService;
use App\Services\AI\OpenAIService;
use App\Services\Parsing\ParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PhpParser\Node;
use Tests\TestCase;

class CodeAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_file_creates_code_analysis_record()
    {
        $openAIService = Mockery::mock(OpenAIService::class);
        $parserService = Mockery::mock(ParserService::class);

        $codeAnalysisService = new CodeAnalysisService($openAIService, $parserService);

        $filePath = 'app/Example.php';
        $ast = [new Node\Stmt\Class_('Example')];

        $parserService->shouldReceive('parseFile')
            ->with($filePath)
            ->once()
            ->andReturn($ast);

        $codeAnalysisService->analyzeFile($filePath);

        $this->assertDatabaseHas('code_analyses', [
            'file_path' => $filePath,
            'class_count' => 1,
            'function_count' => 0,
        ]);
    }
}
