<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AIResult;
use App\Models\CodeAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class AIResultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
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

    #[Test]
    public function it_belongs_to_code_analysis()
    {
        $codeAnalysis = CodeAnalysis::factory()->create();
        $aiResult = AIResult::factory()->create(['code_analysis_id' => $codeAnalysis->id]);

        $this->assertInstanceOf(CodeAnalysis::class, $aiResult->codeAnalysis);
        $this->assertEquals($codeAnalysis->id, $aiResult->codeAnalysis->id);
    }

    #[Test]
    public function it_creates_successful_ai_results()
    {
        $aiResult = AIResult::factory()->success()->create();

        $this->assertEquals('success', $aiResult->metadata['status']);
        $this->assertGreaterThanOrEqual(1, $aiResult->metadata['duration']);
        $this->assertLessThanOrEqual(5, $aiResult->metadata['duration']);
        $this->assertNotNull($aiResult->response_text);
    }

    #[Test]
    public function it_creates_failed_ai_results()
    {
        $aiResult = AIResult::factory()->failure()->create();

        $this->assertEquals('failure', $aiResult->metadata['status']);
        $this->assertGreaterThanOrEqual(6, $aiResult->metadata['duration']);
        $this->assertLessThanOrEqual(10, $aiResult->metadata['duration']);
        $this->assertNull($aiResult->response_text);
    }
}
