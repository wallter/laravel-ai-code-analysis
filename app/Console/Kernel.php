protected $commands = [
    // ... other commands ...
    \App\Console\Commands\ProcessPassesCommand::class,
];
protected function schedule(Schedule $schedule)
{
    // ... existing scheduled tasks ...

    // Schedule the pass processing command
    $schedule->command('passes:process')->everyFiveMinutes();
}
