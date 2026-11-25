<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Automatic rains and the bot's admin sync, both idempotent so a late
        // run never pays twice.
        $schedule->call(function () {
            app(\App\Services\Platform\Rains::class)->tick();
            app(\App\Services\Minecraft\Economy::class)->syncTrustedAdmins();
        })->everyMinute()->name('platform-tick')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
