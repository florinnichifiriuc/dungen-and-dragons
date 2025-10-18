<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('condition-transparency:ping')->hourly()->withoutOverlapping();

        $schedule->job(new \App\Jobs\SendBugReportDigestJob())
            ->dailyAt(config('bug-reporting.digest_time', '08:00'))
            ->timezone(config('bug-reporting.digest_timezone', 'UTC'))
            ->name('bug-report-digest')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
