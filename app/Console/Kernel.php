protected $commands = [
    // ... other commands ...
    \App\Console\Commands\ProcessPassesCommand::class,
    \App\Console\Commands\DbBackup::class,
];
protected function schedule(Schedule $schedule)
{
    // ... existing scheduled tasks ...

    // Schedule the pass processing command
    $schedule->command('passes:process')->everyFiveMinutes();
}
