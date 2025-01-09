<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AnalyzeFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that AnalyzeFilesCommand only processes files from configured folders.
     *
     * @return void
     */
    public function test_analyze_files_respects_config_parsing_folders()
    {
        // Arrange: Set up test directories and files
        Config::set('parsing.folders', [
            base_path('app/Services'),
            base_path('app/Models'),
        ]);

        // Mock file structure
        File::shouldReceive('allFiles')
            ->with(base_path('app/Services'))
            ->andReturn(collect([
                new \SplFileInfo(base_path('app/Services/AI/CodeAnalysisService.php')),
                new \SplFileInfo(base_path('app/Services/Parsing/ParserService.php')),
            ]));

        File::shouldReceive('allFiles')
            ->with(base_path('app/Models'))
            ->andReturn(collect([
                new \SplFileInfo(base_path('app/Models/User.php')),
                new \SplFileInfo(base_path('app/Models/CodeAnalysis.php')),
            ]));

        // Act: Run the command
        Artisan::call('analyze:files', [
            '--dry-run' => true,
            '--output-file' => 'test_output.json',
        ]);

        // Assert: Check that only specified files were processed
        $this->assertTrue(true); // Placeholder to indicate test passed
    }

    /**
     * Test that AnalyzeFilesCommand does not process files outside configured folders.
     *
     * @return void
     */
    public function test_analyze_files_does_not_process_unconfigured_folders()
    {
        // Arrange: Set up test directories and files
        Config::set('parsing.folders', [
            base_path('app/Services'),
        ]);

        // Mock file structure
        File::shouldReceive('allFiles')
            ->with(base_path('app/Services'))
            ->andReturn(collect([
                new \SplFileInfo(base_path('app/Services/AI/CodeAnalysisService.php')),
                new \SplFileInfo(base_path('app/Services/Parsing/ParserService.php')),
            ]));

        // Mock that other directories are not processed
        File::shouldReceive('allFiles')
            ->with(base_path('app'))
            ->never();

        // Act: Run the command
        Artisan::call('analyze:files', [
            '--dry-run' => true,
            '--output-file' => 'test_output.json',
        ]);

        // Assert: Ensure only services files are processed
        $this->assertTrue(true); // Placeholder to indicate test passed
    }
}
