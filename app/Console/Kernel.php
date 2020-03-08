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
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /*
         * Note: to avoid the use of a cache server (Laravel task scheduler onOneServer() relies on it),
         * each scheduled task must ensure it has not been run by another API instance by calling
         * TaskLog::attemptToStart()
         */

        if (config('clubhouse.DeploymentEnvironment') == 'Production') {
            // Let someone know what's been happening in the Clubhouse
            $schedule->command('clubhouse:daily-report')->dailyAt('03:00');

            // Let the photo reviewers know if photos are queued up.
            $schedule->command('clubhouse:photo-pending')->twiceDaily(9, 21);
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
