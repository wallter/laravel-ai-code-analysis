<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class QueueProgressCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:progress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run queued jobs in the background and show a progress bar';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Count total jobs queued.
        $totalJobs = DB::table('jobs')->count();
        if ($totalJobs === 0) {
            $this->info('No jobs in the queue.');
            return 0;
        }

        // 2. Start the queue worker in the background (non-blocking).
        //    - Mac/Linux: append `&` to run in background
        //    - Windows: `start /B php artisan queue:work --stop-when-empty -n`
        exec('php artisan queue:work --stop-when-empty -n > /dev/null 2>&1 &');

        // 3. Set up the progress bar in the current console.
        $output = new ConsoleOutput();
        $progressBar = new ProgressBar($output, $totalJobs);
        $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%%');
        $progressBar->start();

        // 4. Poll the queue periodically until all jobs are processed.
        while (true) {
            $remainingJobs = DB::table('jobs')->count();
            $processed = $totalJobs - $remainingJobs;

            // Update the progress bar to the number of jobs processed.
            $progressBar->setProgress($processed);

            // Break if no jobs remain.
            if ($remainingJobs === 0) {
                break;
            }

            // Sleep briefly before the next check.
            sleep(1);
        }

        $progressBar->finish();
        $this->info("\nAll queued jobs processed!");

        return 0;
    }
}