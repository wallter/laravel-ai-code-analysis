<?php

namespace Tests\Feature;

use App\Models\ParsedItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ParseFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function parse_files_command_stores_data_in_parsed_items_table()
    {
        // Given: a known file with at least one class/function
        $testFilePath = realpath(base_path('app/Services/Parsing/ParserService.php'));

        // Ensure the test file exists
        $this->assertNotFalse($testFilePath, "Test file does not exist at path: app/Services/Parsing/ParserService.php");

        // When: we mock the config and run the parse:files command
        Config::set('parsing.files', [$testFilePath]);
        Artisan::call('parse:files');

        // Then: the parsed_items table should have at least one row
        $this->assertDatabaseCount('parsed_items', 1, "Expected 1 entry in parsed_items table, found {$this->getParsedItemsCount()}.");

        // Optionally: Assert that a row exists with the correct data
        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Class',
            'name' => 'ParserService',
            'file_path' => $testFilePath,
        ]);
    }

    /**
     * Helper method to get the count of parsed_items.
     *
     * @return int
     */
    protected function getParsedItemsCount(): int
    {
        return ParsedItem::count();
    }
}
