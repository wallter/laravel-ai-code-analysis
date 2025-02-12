<?php

namespace App\Console\Commands\Db;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

/**
 * Backup the database to a specified path or default backup directory.
 */
class DbBackupCommand extends Command
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
     *
     * @var string
     */
    protected $description = 'Backup the database to a specified path or default backup directory.';

    /**
     * Execute the console command.
     *
     * @return int Exit status code.
     */
    public function handle(): int
    {
        // Retrieve backup path from the --path option or set default
        $backupPath = $this->option('path') ?: storage_path('app/backups');

        // Ensure the backup directory exists; create it if it doesn't
        if (! File::exists($backupPath)) {
            if (! File::makeDirectory($backupPath, 0755, true)) {
                $this->error("Failed to create backup directory at {$backupPath}.");

                return 1;
            }

            $this->info("Created backup directory at {$backupPath}.");
        }

        // Retrieve the default database connection from config
        $defaultConnection = Config::get('database.default');
        $dbConfig = Config::get("database.connections.{$defaultConnection}");

        if (! $dbConfig) {
            $this->error("Database connection '{$defaultConnection}' is not configured.");

            return 1;
        }

        $dbDriver = $dbConfig['driver'];

        // Ensure the database driver is SQLite
        if ($dbDriver !== 'sqlite') {
            $this->error("Database driver '{$dbDriver}' is not supported by this backup command. Only SQLite is supported.");

            return 1;
        }

        try {
            // Set the correct database path
            $databasePath = $dbConfig['database'];
            if (! File::exists($databasePath)) {
                $this->error("SQLite database file not found at {$databasePath}.");

                return 1;
            }

            $timestamp = Carbon::now()->format('Y_m_d_His');
            $backupFileName = "backup_sqlite_{$timestamp}.sqlite";
            $backupFilePath = "{$backupPath}/{$backupFileName}";

            // Copy the SQLite database file to the backup location
            if (! copy($databasePath, $backupFilePath)) {
                $this->error("Failed to copy SQLite database to {$backupFilePath}.");

                return 1;
            }

            $this->info("SQLite database backed up successfully to {$backupFilePath}.");

            return 0;
        } catch (\Exception $exception) {
            $this->error('Database backup failed: '.$exception->getMessage());

            return 1;
        }
    }
}
