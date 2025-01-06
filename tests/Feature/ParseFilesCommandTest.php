<?php

namespace Tests\Feature;

use App\Models\ParsedItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\File;

class ParseFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function parse_files_command_stores_data_in_parsed_items_table()
    {
        // **Arrange: Create a temporary directory and PHP file with a class**
        $testDirectory = base_path('tests/temp');
        $testFilePath = $testDirectory . '/TestClass.php';
        
        // Ensure the temporary directory exists
        File::makeDirectory($testDirectory, 0755, true, true);
        
        // Create a temporary PHP file with a simple class
        File::put($testFilePath, "<?php\n\nnamespace Tests\Feature;\n\nclass TestClass {}\n");
        
        // **Set the configuration to include the temporary PHP file**
        Config::set('parsing.files', [$testFilePath]);
        
        // **Act: Run the parse:files command**
        Artisan::call('parse:files');
        
        // **Assert: Check that one parsed_item exists with correct data**
        $this->assertDatabaseCount('parsed_items', 1);
        
        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Class',
            'name' => 'TestClass',
            'file_path' => $testFilePath,
        ]);
        
        // **Clean up: Delete the temporary file and directory**
        File::delete($testFilePath);
        File::deleteDirectory($testDirectory);
    }
