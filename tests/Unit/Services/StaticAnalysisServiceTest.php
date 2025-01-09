<?php

namespace Tests\Unit\Services;

use App\Models\CodeAnalysis;
use App\Models\StaticAnalysis;
use App\Services\StaticAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class StaticAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_analysis_successfully_stores_results()
    {
        // Arrange
        $codeAnalysis = CodeAnalysis::factory()->create([
            'file_path' => 'app/Http/Controllers/TestController.php',
        ]);

        // Mock the Process
        $processMock = Mockery::mock(Process::class);
        $processMock->shouldReceive('run')->once();
        $processMock->shouldReceive('isSuccessful')->andReturn(true);
        $processMock->shouldReceive('getOutput')->andReturn(json_encode(['files' => []]));

        // Mock the Process creation
        $this->instance(Process::class, $processMock);

        $service = new StaticAnalysisService;

        // Act
        $staticAnalysis = $service->runAnalysis($codeAnalysis);

        // Assert
        $this->assertInstanceOf(StaticAnalysis::class, $staticAnalysis);
        $this->assertDatabaseHas('static_analyses', [
            'code_analysis_id' => $codeAnalysis->id,
            'tool' => 'PHPStan',
        ]);
    }

    public function test_run_analysis_handles_failure()
    {
        // Arrange
        $codeAnalysis = CodeAnalysis::factory()->create([
            'file_path' => 'app/Http/Controllers/TestController.php',
        ]);

        // Mock the Process
        $processMock = Mockery::mock(Process::class);
        $processMock->shouldReceive('run')->once();
        $processMock->shouldReceive('isSuccessful')->andReturn(false);
        $processMock->shouldReceive('getErrorOutput')->andReturn('Error running PHPStan');

        // Mock the Process creation
        $this->instance(Process::class, $processMock);

        $service = new StaticAnalysisService;

        // Act
        $staticAnalysis = $service->runAnalysis($codeAnalysis);

        // Assert
        $this->assertNull($staticAnalysis);
        $this->assertDatabaseMissing('static_analyses', [
            'code_analysis_id' => $codeAnalysis->id,
            'tool' => 'PHPStan',
        ]);
    }
}
