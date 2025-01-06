<?php

namespace Tests\Feature;

use App\Models\AIScore;
use App\Models\ParsedItem;
use App\Services\AI\DocEnhancer;
use Mockery;
use Tests\TestCase;

class EnhanceDocsCommandTest extends TestCase
{

    public function test_doc_enhancement_command_updates_parsed_items(): void
    {
        // Seed ParsedItem data that needs enhancement
        $parsedItem = ParsedItem::create([
            'type' => 'Class',
            'name' => 'TestClass',
            'file_path' => 'test/path/TestClass.php',
            'line_number' => 10, // or another integer
            'details' => [
                'description' => null,
            ],
        ]);

        // Mock the DocEnhancer service
        $this->mock(DocEnhancer::class, function ($mock) use ($parsedItem) {
            $mock->shouldReceive('enhanceDescription')
                ->once()
                ->with(\Mockery::on(fn($arg) => $arg->id === $parsedItem->id))
                ->andReturn('Enhanced description');
        });

        // Call the doc:enhance Artisan command
        $this->artisan('doc:enhance')
            ->expectsOutput("Enhancing documentation for: {$parsedItem->type} {$parsedItem->name}")
            ->expectsOutput("Updated description for {$parsedItem->name}.")
            ->assertExitCode(0);

        // Ensure annotations are correctly parsed
        $this->assertNotEmpty($parsedItem->details['annotations'], 'Annotations should not be empty.');
        $this->assertArrayHasKey('exampleAnnotation', $parsedItem->details['annotations'], 'Specific annotation should be present.');
        $this->assertEquals('Enhanced description', $parsedItem->fresh()->details['description']);
    }

    public function test_scoring_pass_creates_scores_correctly(): void
    {
        // Create a CodeAnalysis instance
        $analysis = CodeAnalysis::create([
            'file_path' => 'test/path/TestClass.php',
            'ast' => [],
            'analysis' => [],
            'ai_output' => [],
            'current_pass' => 0,
            'completed_passes' => [],
        ]);

        // Mock AIResult entries for previous passes
        AIResult::create([
            'code_analysis_id' => $analysis->id,
            'pass_name' => 'doc_generation',
            'response_text' => 'Documentation Score: 85.0',
            'prompt_text' => '...',
            'metadata' => [],
        ]);

        AIResult::create([
            'code_analysis_id' => $analysis->id,
            'pass_name' => 'functional_analysis',
            'response_text' => 'Functionality Score: 90.0',
            'prompt_text' => '...',
            'metadata' => [],
        ]);

        AIResult::create([
            'code_analysis_id' => $analysis->id,
            'pass_name' => 'style_convention',
            'response_text' => 'Style Score: 80.0',
            'prompt_text' => '...',
            'metadata' => [],
        ]);

        // Simulate executing the scoring pass
        $codeAnalysisService = $this->app->make(CodeAnalysisService::class);
        $codeAnalysisService->computeAndStoreScores($analysis);

        // Refresh the model
        $analysis->refresh();

        // Assert AIScore records are correctly created
        $this->assertCount(4, $analysis->aiScores, 'There should be 4 AIScore records.');

        $documentationScore = $analysis->aiScores()->where('operation', 'documentation')->first();
        $functionalityScore = $analysis->aiScores()->where('operation', 'functionality')->first();
        $styleScore = $analysis->aiScores()->where('operation', 'style')->first();
        $overallScore = $analysis->aiScores()->where('operation', 'overall')->first();

        $this->assertNotNull($documentationScore, 'Documentation score should exist.');
        $this->assertEquals(85.0, $documentationScore->score, 'Documentation score should be 85.0.');

        $this->assertNotNull($functionalityScore, 'Functionality score should exist.');
        $this->assertEquals(90.0, $functionalityScore->score, 'Functionality score should be 90.0.');

        $this->assertNotNull($styleScore, 'Style score should exist.');
        $this->assertEquals(80.0, $styleScore->score, 'Style score should be 80.0.');

        $this->assertNotNull($overallScore, 'Overall score should exist.');
        $this->assertEquals(85.0, $overallScore->score, 'Overall score should be 85.0.');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
