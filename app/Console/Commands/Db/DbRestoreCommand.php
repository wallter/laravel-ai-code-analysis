<?php

namespace App\Console\Commands\Db;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

/**
 * Restore the database from a specified backup file or the most recent backup.
 */
class DbRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Options:
     * --path= : Optional path to the backup file to restore.
     */
    protected $signature = 'db:backup:restore {--path= : Optional path to the backup file to restore}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the database from a specified backup file or the most recent backup if no path is provided.';

    /**
     * Execute the console command.
     *
     * @return int Exit status code.
     */
    public function handle(): int
    {
        // Retrieve the backup path from the --path option or determine the most recent backup
        $backupPath = $this->option('path');

        if ($backupPath) {
            // Normalize the provided backup path
            $backupPath = $this->normalizePath($backupPath);

            // Check if the specified backup file exists
            if (!File::exists($backupPath)) {
                $this->error("Specified backup file not found at {$backupPath}.");
                return 1;
            }
        } else {
            // Define the default backup directory
            $backupDirectory = storage_path('app/backups');

            // Check if the backup directory exists
            if (!File::exists($backupDirectory)) {
                $this->error("Backup directory not found at {$backupDirectory}.");
                return 1;
            }

            // Retrieve all backup files, assuming they follow the naming convention 'backup_sqlite_YYYY_MM_DD_HHMMSS.sqlite'
            $backupFiles = File::files($backupDirectory);

            // Filter SQLite backup files
            $sqliteBackups = collect($backupFiles)->filter(function ($file) {
                return preg_match('/^backup_sqlite_\d{4}_\d{2}_\d{2}_\d{6}\.sqlite$/', $file->getFilename());
            });

            if ($sqliteBackups->isEmpty()) {
                $this->error("No SQLite backup files found in {$backupDirectory}.");
                return 1;
            }

            // Sort backups by creation time descending to get the most recent
            $mostRecentBackup = $sqliteBackups->sortByDesc(function ($file) {
                return $file->getCTime();
            })->first()->getRealPath();

            $backupPath = $mostRecentBackup;
            $this->info("No backup path provided. Using the most recent backup: {$backupPath}");
        }

        // Retrieve the default database connection from config
        $defaultConnection = Config::get('database.default');
        $dbConfig = Config::get("database.connections.{$defaultConnection}");

        if (!$dbConfig) {
            $this->error("Database connection '{$defaultConnection}' is not configured.");
            return 1;
        }

        $dbDriver = $dbConfig['driver'];

        // Ensure the database driver is SQLite
        if ($dbDriver !== 'sqlite') {
            $this->error("Database driver '{$dbDriver}' is not supported by this restore command. Only SQLite is supported.");
            return 1;
        }

        try {
            // Determine the database path
            $databasePath = $dbConfig['database'];

            // Ensure the directory for the database exists
            $databaseDir = dirname($databasePath);
            if (!File::exists($databaseDir)) {
                if (!File::makeDirectory($databaseDir, 0755, true)) {
                    $this->error("Failed to create database directory at {$databaseDir}.");
                    return 1;
                }
                $this->info("Created database directory at {$databaseDir}.");
            }

            // Check if the database file exists and confirm overwrite
            if (File::exists($databasePath)) {
                if (!$this->confirm("Are you sure you want to overwrite the existing database at {$databasePath}?")) {
                    $this->info("Database restore aborted.");
                    return 0;
                }
            }

            // Copy the backup file to the database path
            if (!copy($backupPath, $databasePath)) {
                $this->error("Failed to restore the database from {$backupPath} to {$databasePath}.");
                return 1;
            }

            $this->info("Database restored successfully from {$backupPath} to {$databasePath}.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Database restore failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Normalize a given path to its absolute form.
     *
     * @param string $path The path to normalize.
     * @return string The normalized absolute path.
     */
    protected function normalizePath(string $path): string
    {
        return File::isAbsolutePath($path) ? $path : base_path($path);
    }
}
