<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Keep season dates in the JSON-LD current, ahead of the nightly sync
        $schedule->command('attractions:refresh-structured-data')
            ->dailyAt('01:45')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/wp-sync.log'));

        // Sync attractions to WordPress daily at 2:00 AM
        $schedule->command('attractions:sync-wordpress --language=both')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/wp-sync.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
