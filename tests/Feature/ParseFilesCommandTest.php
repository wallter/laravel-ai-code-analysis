<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use App\Models\ParsedItem;

class ParseFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function testParseFilesCommandStoresDataInParsedItemsTable()
    {
        // Given: a known file with at least one class/function
        $testFilePath = base_path('app/Services/Parsing/ParserService.php');

        // When: we mock the config and run the parse:files command
        Config::set('parsing.files', [$testFilePath]);
        Artisan::call('parse:files');

        // Then: the parsed_items table should have at least one row
        $this->assertDatabaseCount('parsed_items', 1);

        // Optionally: Assert that a row exists with the correct data
        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Class',
            'name' => 'ParserService',
            'file_path' => $testFilePath,
        ]);
    }
}
