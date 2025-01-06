<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\AIScore;
use App\Models\CodeAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class AIScoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes()
    {
        $aiscore = AIScore::factory()->make();

        $this->assertEquals([
            'code_analysis_id',
            'operation',
            'score',
        ], $aiscore->getFillable());
    }

    #[Test]
    public function it_belongs_to_code_analysis()
    {
        $codeAnalysis = CodeAnalysis::factory()->create();
        $aiscore = AIScore::factory()->create(['code_analysis_id' => $codeAnalysis->id]);

        $this->assertInstanceOf(CodeAnalysis::class, $aiscore->codeAnalysis);
        $this->assertEquals($codeAnalysis->id, $aiscore->codeAnalysis->id);
    }

    #[Test]
    public function it_creates_high_scores()
    {
        $aiscore = AIScore::factory()->high()->create();

        $this->assertGreaterThanOrEqual(80, $aiscore->score);
        $this->assertLessThanOrEqual(100, $aiscore->score);
    }

    #[Test]
    public function it_creates_medium_scores()
    {
        $aiscore = AIScore::factory()->medium()->create();

        $this->assertGreaterThanOrEqual(50, $aiscore->score);
        $this->assertLessThan(80, $aiscore->score);
    }

    #[Test]
    public function it_creates_low_scores()
    {
        $aiscore = AIScore::factory()->low()->create();

        $this->assertGreaterThanOrEqual(0, $aiscore->score);
        $this->assertLessThan(50, $aiscore->score);
    }
}
