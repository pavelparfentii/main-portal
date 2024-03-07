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
        // Update accounts data
         $schedule->command('app:update-user-data-command')->dailyAt('00:30');

        // Update discord data
        $schedule->command('app:get-discord-roles-for-accounts-command')->dailyAt('00:45');

         //Safesoul activity
        $schedule->command('app:safe-soul-activity-command')->weeklyOn(1, '01:00');

        //Safesoul achieve
        $schedule->command('app:safe-soul-achieve-command')->dailyAt('01:30');

        //Safesoul invites
        $schedule->command('app:safe-soul-invites-command')->dailyAt('02:00');

        //Safesoul 100 reports
        $schedule->command('app:safe-soul100-reports-command')->dailyAt('02:30');

        //Safesoulclub and SafesoulEth twitter posts
        $schedule->command('app:twitter-posts-s-s-mention-command')->weeklyOn(1, '03:00');

        //Igor twitter posts
        $schedule->command('app:twitter-posts-igor-mention-command')->weeklyOn(1, '03:30');

        //3 Twitter accounts likes and retweets
        $schedule->command('app:twitter3-accounts-retweets-likes-points')->weeklyOn(1, '06:00');

        //Digital Animal owners
        $schedule->command('app:digital-animals-owners-points-command')->dailyAt('04:00');

        //Digital Animals lords 20 token
        $schedule->command('app:digital-animals-lord20-command')->dailyAt('04:30');

        //Digital Animals roles
        $schedule->command('app:digital-animals-roles-command')->dailyAt('05:00');

        //Digital Animals long owner
        $schedule->command('app:digital-animals-long-range-owner-points-command')->dailyAt('05:30');
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
