<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\CodeAnalysis;
use App\Models\AIScore;
use App\Models\AIResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CodeAnalysisTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $codeAnalysis = CodeAnalysis::factory()->make();

        $this->assertEquals([
            'file_path',
            'ast',
            'analysis',
            'ai_output',
            'current_pass',
            'completed_passes',
        ], $codeAnalysis->getFillable());
    }

    /** @test */
    public function it_has_many_aiscores()
    {
        $codeAnalysis = CodeAnalysis::factory()->create();
        $aiscores = AIScore::factory()->count(3)->create(['code_analysis_id' => $codeAnalysis->id]);

        $this->assertCount(3, $codeAnalysis->aiScores);
        $this->assertInstanceOf(AIScore::class, $codeAnalysis->aiScores->first());
    }

    /** @test */
    public function it_has_many_airesults()
    {
        $codeAnalysis = CodeAnalysis::factory()->create();
        $aiResults = AIResult::factory()->count(2)->create(['code_analysis_id' => $codeAnalysis->id]);

        $this->assertCount(2, $codeAnalysis->aiResults);
        $this->assertInstanceOf(AIResult::class, $codeAnalysis->aiResults->first());
    }
}
