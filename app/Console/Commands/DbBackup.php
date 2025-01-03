<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
    protected $description = 'Backup the database to a specified path or default backup directory.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get backup path from option or set default
        $backupPath = $this->option('path') ?: storage_path('app/backups');

        // Ensure the backup directory exists
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
            $this->info("Created backup directory at {$backupPath}.");
        }

        // Get database configuration
        $defaultConnection = Config::get('database.default');
        $dbConfig = Config::get("database.connections.{$defaultConnection}");

        if (!$dbConfig) {
            $this->error("Database connection '{$defaultConnection}' is not configured.");
            return 1;
        }

        $dbDriver = $dbConfig['driver'];

        // Generate timestamp for the backup file
        $timestamp = date('Y_m_d_His');

        try {
            if ($dbDriver === 'sqlite') {
                $databasePath = base_path($dbConfig['database']);
                if (!file_exists($databasePath)) {
                    $this->error("SQLite database file not found at {$databasePath}.");
                    return 1;
                }

                $backupFileName = "backup_sqlite_{$timestamp}.sqlite";
                $backupFilePath = "{$backupPath}/{$backupFileName}";

                if (!copy($databasePath, $backupFilePath)) {
                    $this->error("Failed to copy SQLite database to {$backupFilePath}.");
                    return 1;
                }

                $this->info("SQLite database backed up successfully to {$backupFilePath}.");

            } elseif ($dbDriver === 'mysql') {
                $host = $dbConfig['host'] ?? '127.0.0.1';
                $port = $dbConfig['port'] ?? '3306';
                $database = $dbConfig['database'];
                $username = $dbConfig['username'];
                $password = $dbConfig['password'];

                $backupFileName = "backup_mysql_{$timestamp}.sql";
                $backupFilePath = "{$backupPath}/{$backupFileName}";

                // Construct the mysqldump command
                $command = [
                    'mysqldump',
                    '-h', $host,
                    '-P', $port,
                    '-u', $username,
                    "--password={$password}",
                    $database,
                ];

                $process = new Process($command);
                $process->setTimeout(300);

                // Start the process and capture the output
                $process->run();

                // Check for errors
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                // Save the dump to the backup file
                file_put_contents($backupFilePath, $process->getOutput());

                $this->info("MySQL database backed up successfully to {$backupFilePath}.");

            } else {
                $this->error("The '{$dbDriver}' driver is not supported by this backup command.");
                return 1;
            }

            return 0;
        } catch (ProcessFailedException $e) {
            $this->error("mysqldump failed: " . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error("Database backup failed: " . $e->getMessage());
            return 1;
        }
    }
}
