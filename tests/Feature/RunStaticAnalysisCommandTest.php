<?php

namespace Tests\Feature;

use App\Models\CodeAnalysis;
use App\Models\StaticAnalysis;
use App\Services\StaticAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class RunStaticAnalysisCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockConsoleOutput = false;
    }
    use RefreshDatabase;

    public function test_command_runs_static_analysis_successfully()
    {
        // Arrange
        $codeAnalysis = CodeAnalysis::factory()->create([
            'file_path' => 'app/Http/Controllers/TestController.php',
        ]);

        // Mock the StaticAnalysisService
        $serviceMock = Mockery::mock(StaticAnalysisService::class);
        $serviceMock->shouldReceive('runAnalysis')
            ->with($codeAnalysis)
            ->andReturn(StaticAnalysis::factory()->make([
                'code_analysis_id' => $codeAnalysis->id,
                'tool' => 'PHPStan',
                'results' => ['files' => []],
            ]));

        $this->app->instance(StaticAnalysisService::class, $serviceMock);

        // Act
        Artisan::call('static-analysis:run', ['code_analysis_id' => $codeAnalysis->id]);

        // Assert
        $this->assertDatabaseHas('static_analyses', [
            'code_analysis_id' => $codeAnalysis->id,
            'tool' => 'PHPStan',
        ]);

        $this->assertStringContainsString('Static analysis completed and results stored.', Artisan::output());
    }

    public function test_command_handles_nonexistent_code_analysis()
    {
        // Act
        Artisan::call('static-analysis:run', ['code_analysis_id' => 999]);

        // Assert
        $this->assertStringContainsString('CodeAnalysis with ID 999 not found.', Artisan::output());
    }
}
