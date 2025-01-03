<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

/**
 * DbBackup Command
 *
 * This command backs up the SQLite database by copying the database file
 * to a specified backup directory located at 'database/backups'.
 */
class DbBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Options:
     * --path= : Optional path to store the backup file.
     */
    protected $signature = 'db:backup {--path= : Optional path to store the backup file}';

    /**
     * The console command description.
     */
    protected $description = 'Backup the SQLite database located at database/database.sqlite to database/backups or a specified path.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Define source and default backup paths using database_path()
        $sourceDatabase = database_path('database.sqlite');
        $backupPath = $this->option('path') ?: database_path('backups');

        // Ensure the source database file exists
        if (!File::exists($sourceDatabase)) {
            $this->error("SQLite database file not found at {$sourceDatabase}.");
            return 1;
        }

        // Ensure the backup directory exists; create it if it doesn't
        if (!File::exists($backupPath)) {
            if (!File::makeDirectory($backupPath, 0755, true)) {
                $this->error("Failed to create backup directory at {$backupPath}.");
                return 1;
            }
            $this->info("Created backup directory at {$backupPath}.");
        } else {
            $this->info("Backup directory exists at {$backupPath}.");
        }

        try {
            // Generate a timestamped backup file name
            $timestamp = Carbon::now()->format('Y_m_d_His');
            $backupFileName = "backup_sqlite_{$timestamp}.sqlite";
            $backupFilePath = "{$backupPath}/{$backupFileName}";

            // Copy the SQLite database file to the backup location
            if (!copy($sourceDatabase, $backupFilePath)) {
                $this->error("Failed to copy SQLite database to {$backupFilePath}.");
                return 1;
            }

            $this->info("SQLite database backed up successfully to {$backupFilePath}.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Database backup failed: " . $e->getMessage());
            return 1;
        }
    }
}
