<?php

namespace Tests\Feature\Models;

use App\Models\AIResult;
use App\Models\AIScore;
use App\Models\CodeAnalysis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CodeAnalysisTest extends TestCase
{
    #[Test]
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

    #[Test]
    public function it_has_many_aiscores()
    {
        $codeAnalysis = CodeAnalysis::factory()->create();
        $aiscores = AIScore::factory()->count(3)->create(['code_analysis_id' => $codeAnalysis->id]);

        $this->assertCount(3, $codeAnalysis->aiScores);
        $this->assertInstanceOf(AIScore::class, $codeAnalysis->aiScores->first());
    }

    #[Test]
    public function it_has_many_airesults()
    {
        $codeAnalysis = CodeAnalysis::factory()->create();
        $aiResults = AIResult::factory()->count(2)->create(['code_analysis_id' => $codeAnalysis->id]);

        $this->assertCount(2, $codeAnalysis->aiResults);
        $this->assertInstanceOf(AIResult::class, $codeAnalysis->aiResults->first());
    }

    #[Test]
    public function it_creates_completed_code_analysis()
    {
        $codeAnalysis = CodeAnalysis::factory()->completed()->create();

        $this->assertEquals(3, $codeAnalysis->current_pass);
        $this->assertIsArray($codeAnalysis->completed_passes);
        $this->assertCount(2, $codeAnalysis->completed_passes);
        $this->assertContains('pass1', $codeAnalysis->completed_passes);
        $this->assertContains('pass2', $codeAnalysis->completed_passes);
    }

    #[Test]
    public function it_creates_code_analysis_with_ai_output()
    {
        $codeAnalysis = CodeAnalysis::factory()->withAiOutput()->create();

        $this->assertIsArray($codeAnalysis->ai_output);
        $this->assertArrayHasKey('summary', $codeAnalysis->ai_output);
        $this->assertArrayHasKey('recommendations', $codeAnalysis->ai_output);
        $this->assertNotEmpty($codeAnalysis->ai_output['summary']);
        $this->assertNotEmpty($codeAnalysis->ai_output['recommendations']);
    }
}
