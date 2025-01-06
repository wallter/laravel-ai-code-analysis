<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AIScore;
use App\Models\CodeAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AIScoreTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $aiscore = AIScore::factory()->make();

        $this->assertEquals([
            'code_analysis_id',
            'operation',
            'score',
        ], $aiscore->getFillable());
    }

    /** @test */
    public function it_belongs_to_code_analysis()
    {
        $codeAnalysis = CodeAnalysis::factory()->create();
        $aiscore = AIScore::factory()->create(['code_analysis_id' => $codeAnalysis->id]);

        $this->assertInstanceOf(CodeAnalysis::class, $aiscore->codeAnalysis);
        $this->assertEquals($codeAnalysis->id, $aiscore->codeAnalysis->id);
    }
}
