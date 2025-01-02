<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class ResetDbCommand extends Command
{
    protected $signature = 'db:reset';
    protected $description = 'Resets the SQLite database file and runs migrations fresh.';

    public function handle()
    {
        // 1) Identify the path to your SQLite DB
        // If you're storing your DB at database/database.sqlite or something else,
        // adapt this line as needed:
        $dbPath = database_path('database.sqlite'); 

        // 2) Remove it if it exists
        if (File::exists($dbPath)) {
            File::delete($dbPath);
            $this->info("Deleted existing SQLite database at: {$dbPath}");
        } else {
            $this->info("No existing SQLite database found at: {$dbPath}");
        }

        // 3) Re-run migrations
        // This will recreate the DB file if your migrations define that.
        $exitCode = Artisan::call('migrate', [
            '--force' => true, // needed in non-interactive or production-like environments
        ]);

        if ($exitCode === 0) {
            $this->info("Database successfully reset and migrations run.");
        } else {
            $this->error("Error running migrations. Check the logs above.");
        }

        return $exitCode;
    }
}