<?php

namespace App\Console\Commands\Queue;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListQueuedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --type= : Filter jobs by their class name (e.g. "App\Jobs\ProcessAnalysisPassJob").
     */
    protected $signature = 'queue:list {--type= : Filter jobs by their type (class name)}';

    /**
     * The console command description.
     */
    protected $description = 'List queued jobs without running them, with optional filtering by job type';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $typeFilter = $this->option('type');

        // Retrieve all jobs from the "jobs" table
        $jobs = DB::table('jobs')->get();

        if ($jobs->isEmpty()) {
            $this->info('No jobs found in the queue.');

            return;
        }

        $headers = ['ID', 'Type', 'Queue', 'Attempts', 'Job Data'];
        $rows = [];

        foreach ($jobs as $job) {
            $payload = json_decode($job->payload);

            // If payload fails to decode, skip or show an error
            if (! $payload) {
                $this->error("Failed to decode payload for Job ID: {$job->id}");

                continue;
            }

            // Extract the job class name (displayName) from the payload
            $jobClass = $payload->displayName ?? 'Unknown';

            // Filter out jobs if they don't match the requested type
            if ($typeFilter && $jobClass !== $typeFilter) {
                continue;
            }

            // Extract data without running the job
            $jobData = $this->extractJobData($payload);

            // Prepare a table row
            $rows[] = [
                $job->id,
                $jobClass,
                $job->queue,
                $job->attempts,
                json_encode($jobData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
        }

        // Display results
        if (empty($rows)) {
            $this->info('No matching jobs found.');
        } else {
            $this->table($headers, $rows);
        }
    }

    /**
     * Extract job-specific data without calling the job's handle() method.
     */
    private function extractJobData(object $payload): array
    {
        // Some queue drivers store the serialized Job in data->command
        if (isset($payload->data->command)) {
            // Unserialize the command object to access its properties
            // This does NOT call the 'handle()' method.
            $command = @unserialize($payload->data->command);

            if (is_object($command)) {
                return get_object_vars($command);
            }
        }

        return [];
    }
}
