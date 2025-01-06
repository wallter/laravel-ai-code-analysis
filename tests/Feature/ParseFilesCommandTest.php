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
        $this->markTestIncomplete('Not implemented yet.');

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

    #[Test]
    public function parse_files_command_stores_multiple_classes_in_parsed_items_table()
    {
        $this->markTestIncomplete('Not implemented yet.');

        // **Arrange: Create a temporary directory and multiple PHP files with classes**
        $testDirectory = base_path('tests/temp/multiple_classes');
        $testFilePath1 = "{$testDirectory}/TestClass1.php";
        $testFilePath2 = "{$testDirectory}/TestClass2.php";

        // Track the temporary directory for cleanup
        $this->tempDirectories[] = $testDirectory;

        // Ensure the temporary directory exists
        File::makeDirectory($testDirectory, 0755, true, true);

        // Create multiple PHP files with simple classes
        File::put($testFilePath1, "<?php\n\nnamespace Tests\Feature;\n\nclass TestClass1 {}\n");
        File::put($testFilePath2, "<?php\n\nnamespace Tests\Feature;\n\nclass TestClass2 {}\n");
        
        // **Set the configuration to include the temporary directory**
        Config::set('parsing.folders', [$testDirectory]);
        
        // **Act: Run the parse:files command**
        Artisan::call('parse:files');
        
        // **Assert: Check that two parsed_items exist with correct data**
        $this->assertDatabaseCount('parsed_items', 2);
        
        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Class',
            'name' => 'TestClass1',
            'file_path' => $testFilePath1,
        ]);
        
        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Class',
            'name' => 'TestClass2',
            'file_path' => $testFilePath2,
        ]);
    }

    #[Test]
    public function parse_files_command_stores_traits_in_parsed_items_table()
    {
        $this->markTestIncomplete('Not implemented yet.');

        // **Arrange: Create a temporary directory and a PHP file with a trait**
        $testDirectory = base_path('tests/temp/traits');
        $testFilePath = "{$testDirectory}/TestTrait.php";

        // Track the temporary directory
        $this->tempDirectories[] = $testDirectory;

        // Ensure the temporary directory exists
        File::makeDirectory($testDirectory, 0755, true, true);
        
        // Create a PHP file with a trait
        File::put($testFilePath, "<?php\n\nnamespace Tests\Feature;\n\ntrait TestTrait {}\n");
        
        // **Set the configuration to include the temporary directory**
        Config::set('parsing.folders', [$testDirectory]);
        
        // **Act: Run the parse:files command**
        Artisan::call('parse:files');
        
        // **Assert: Check that one parsed_item exists with correct data**
        $this->assertDatabaseCount('parsed_items', 1);
        
        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Trait',
            'name' => 'TestTrait',
            'file_path' => $testFilePath,
        ]);
    }

    #[Test]
    public function parse_files_command_handles_files_with_syntax_errors()
    {
        $this->markTestIncomplete('Not implemented yet.');

        // **Arrange: Create a temporary directory and a PHP file with a syntax error**
        $testDirectory = base_path('tests/temp/syntax_errors');
        $testFilePath = "{$testDirectory}/Invalid.php";

        // Track the temporary directory
        $this->tempDirectories[] = $testDirectory;

        // Ensure the temporary directory exists
        File::makeDirectory($testDirectory, 0755, true, true);
        
        // Create a PHP file with a syntax error (missing closing bracket)
        File::put($testFilePath, "<?php\n\nnamespace Tests\Feature;\n\nclass InvalidClass {\n");
        
        // **Set the configuration to include the temporary PHP file**
        Config::set('parsing.files', [$testFilePath]);
        
        // **Act: Run the parse:files command**
        Artisan::call('parse:files');
        
        // **Assert: Ensure that no parsed_items were created**
        $this->assertDatabaseCount('parsed_items', 0);
    }

    #[Test]
    public function parse_files_command_handles_no_files_to_parse_gracefully()
    {
        $this->markTestIncomplete('Not implemented yet.');

        // **Arrange: Ensure 'parsing.files' is empty**
        Config::set('parsing.files', []);
        Config::set('parsing.folders', []);
        
        // **Act: Run the parse:files command**
        $exitCode = Artisan::call('parse:files');
        
        // **Assert: Command exits with success status**
        $this->assertEquals(0, $exitCode);
        
        // **Assert: No parsed_items entries**
        $this->assertDatabaseCount('parsed_items', 0);
    }

    #[Test]
    public function parse_files_command_applies_limit_class_option()
    {
        $this->markTestIncomplete('Not implemented yet.');

        // **Arrange: Create a temporary directory and multiple PHP files with classes**
        $testDirectory = base_path('tests/temp/limit_class');
        $testFilePath1 = "{$testDirectory}/TestClass1.php";
        $testFilePath2 = "{$testDirectory}/TestClass2.php";
        $testFilePath3 = "{$testDirectory}/TestClass3.php";

        // Track the temporary directory
        $this->tempDirectories[] = $testDirectory;

        // Ensure the temporary directory exists
        File::makeDirectory($testDirectory, 0755, true, true);
        
        // Create multiple PHP files with simple classes
        File::put($testFilePath1, "<?php\n\nnamespace Tests\Feature;\n\nclass TestClass1 {}\n");
        File::put($testFilePath2, "<?php\n\nnamespace Tests\Feature;\n\nclass TestClass2 {}\n");
        File::put($testFilePath3, "<?php\n\nnamespace Tests\Feature;\n\nclass TestClass3 {}\n");
        
        // **Set the configuration to include the temporary directory**
        Config::set('parsing.folders', [$testDirectory]);
        
        // **Act: Run the parse:files command with --limit-class=2**
        Artisan::call('parse:files', ['--limit-class' => 2]);
        
        // **Assert: Only two parsed_items are created**
        $this->assertDatabaseCount('parsed_items', 2);
        
        // **Assert that the first two classes exist**
        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Class',
            'name' => 'TestClass1',
            'file_path' => $testFilePath1,
        ]);

        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Class',
            'name' => 'TestClass2',
            'file_path' => $testFilePath2,
        ]);

        // **Assert that the third class was not parsed**
        $this->assertDatabaseMissing('parsed_items', [
            'type' => 'Class',
            'name' => 'TestClass3',
            'file_path' => $testFilePath3,
        ]);
    }

    #[Test]
    public function parse_files_command_applies_limit_method_option()
    {
        $this->markTestIncomplete('Not implemented yet.');

        // **Arrange: Create a temporary directory and a PHP file with a class containing multiple methods**
        $testDirectory = base_path('tests/temp/limit_method');
        $testFilePath = "{$testDirectory}/ClassWithMethods.php";

        // Track the temporary directory
        $this->tempDirectories[] = $testDirectory;

        // Ensure the temporary directory exists
        File::makeDirectory($testDirectory, 0755, true, true);
        
        // Create a PHP file with a class containing multiple methods
        $phpCode = "<?php\n\nnamespace Tests\Feature;\n\nclass ClassWithMethods {\n";
        for ($i = 1; $i <= 5; $i++) {
            $phpCode .= "    public function method{$i}() {}\n";
        }
        $phpCode .= "}\n";
        File::put($testFilePath, $phpCode);
        
        // **Set the configuration to include the temporary PHP file**
        Config::set('parsing.files', [$testFilePath]);
        
        // **Act: Run the parse:files command with --limit-method=2**
        Artisan::call('parse:files', ['--limit-method' => 2]);
        
        // **Assert: Check that the class is parsed with only two methods in details**
        $this->assertDatabaseCount('parsed_items', 1);
        
        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Class',
            'name' => 'ClassWithMethods',
            'file_path' => $testFilePath,
        ]);

        // Retrieve the parsed item
        $parsedItem = ParsedItem::where('name', 'ClassWithMethods')->first();

        // Decode the 'details' JSON field
        $details = json_decode($parsedItem->details, true);

        // **Assert that only two methods are present**
        $this->assertCount(2, $details['methods'] ?? []);

        // **Assert that the methods are method1 and method2**
        $this->assertContains('method1', $details['methods']);
        $this->assertContains('method2', $details['methods']);
    }

    #[Test]
    public function parse_files_command_applies_filter_option()
    {
        $this->markTestIncomplete('Not implemented yet.');
        
        // **Arrange: Create a temporary directory and PHP files with classes**
        $testDirectory = base_path('tests/temp/filter_test');
        $testFilePath1 = "{$testDirectory}/Alpha.php";
        $testFilePath2 = "{$testDirectory}/Beta.php";

        // Track the temporary directory
        $this->tempDirectories[] = $testDirectory;

        // Ensure the temporary directory exists
        File::makeDirectory($testDirectory, 0755, true, true);
        
        // Create PHP files with classes
        File::put($testFilePath1, "<?php\n\nnamespace Tests\Feature;\n\nclass Alpha {}\n");
        File::put($testFilePath2, "<?php\n\nnamespace Tests\Feature;\n\nclass Beta {}\n");
        
        // **Set the configuration to include the temporary directory**
        Config::set('parsing.folders', [$testDirectory]);
        
        // **Act: Run the parse:files command with --filter=Alpha**
        Artisan::call('parse:files', ['--filter' => 'Alpha']);
        
        // **Assert: Only the 'Alpha' class is parsed and stored**
        $this->assertDatabaseCount('parsed_items', 1);
        
        $this->assertDatabaseHas('parsed_items', [
            'type' => 'Class',
            'name' => 'Alpha',
            'file_path' => $testFilePath1,
        ]);
        
        $this->assertDatabaseMissing('parsed_items', [
            'type' => 'Class',
            'name' => 'Beta',
            'file_path' => $testFilePath2,
        ]);
    }
}
