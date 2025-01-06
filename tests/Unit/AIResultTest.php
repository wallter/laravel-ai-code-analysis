<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AIResult;
use App\Models\CodeAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AIResultTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $aiResult = AIResult::factory()->make();

        $this->assertEquals([
            'code_analysis_id',
            'pass_name',
            'prompt_text',
            'response_text',
            'metadata',
        ], $aiResult->getFillable());
    }

    /** @test */
    public function it_belongs_to_code_analysis()
    {
        $codeAnalysis = CodeAnalysis::factory()->create();
        $aiResult = AIResult::factory()->create(['code_analysis_id' => $codeAnalysis->id]);

        $this->assertInstanceOf(CodeAnalysis::class, $aiResult->codeAnalysis);
        $this->assertEquals($codeAnalysis->id, $aiResult->codeAnalysis->id);
    }
}
