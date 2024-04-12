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

        //Recout points weekly
        $schedule->command('app:recount-points')->weeklyOn(1, '00:01');

        // Update accounts data
         $schedule->command('app:update-user-data-command')->dailyAt('00:30');

        // Update discord data
        $schedule->command('app:get-discord-roles-for-accounts-command')->weeklyOn(1, '00:45');

         //Safesoul activity
        $schedule->command('app:safe-soul-activity-command')->weeklyOn(0, '01:00');

        //Safesoul achieve
        $schedule->command('app:safe-soul-achieve-command')->weeklyOn(0, '01:30');

        //Safesoul invites
        $schedule->command('app:safe-soul-invites-command')->weeklyOn(0, '02:00');

        //Safesoul 100 reports
        $schedule->command('app:safe-soul100-reports-command')->weeklyOn(0,'02:30');

        //Safesoulclub and SafesoulEth twitter posts
        $schedule->command('app:twitter-posts-s-s-mention-command')->weeklyOn(0, '03:00');

        //Igor twitter posts
        $schedule->command('app:twitter-posts-igor-mention-command')->weeklyOn(0, '03:30');

        //3 Twitter accounts likes and retweets
        $schedule->command('app:twitter3-accounts-retweets-likes-points')->weeklyOn(0, '06:00');

        //Digital Animal owners
        $schedule->command('app:digital-animals-owners-points-command')->weeklyOn(0, '04:00');

        //Digital Animals lords 20 token
        $schedule->command('app:digital-animals-lord20-command')->weeklyOn(0, '04:30');

        //Digital Animals roles
        $schedule->command('app:digital-animals-roles-command')->weeklyOn(0, '05:00');

        //Digital Animals long owner
        $schedule->command('app:digital-animals-long-range-owner-points-command')->weeklyOn(0, '05:30');

        //Digital Animal Committed Pass
        $schedule->command('app:digital-animals-commited-pass-points-command')->weeklyOn(0,'06:30');

        //Digital Animal Soulborne Pass
        $schedule->command('app:digital-animals-soulborne-pass-points-calculation-command')->weeklyOn(0,'07:30');

        //Digital Animal SoulReaper Pass
        $schedule->command('app:digital-animals-soul-reaper-pass-points-calculation-command')->weeklyOn(0,'08:00');

        //Digital Animal Lord oF The Reapers
        $schedule->command('app:digital-animals-lord-of-the-reapers-pass-points-calculation-command')->weeklyOn(0,'09:00');

        //Digital Animal Metaverse Animals Owner
        $schedule->command('app:digital-animals-metaverase-owners-command')->weeklyOn(0,'09:30');

        //Digital Animals Original Minters
        $schedule->command('app:digital-animals-original-minter-command')->weeklyOn(0, '10:00');

        $schedule->command('app:digital-animals-sale-points-command')->dailyAt('23:50');

        //Digital Animals Minted Never Sold
        $schedule->command('app:digital-animals-minted-never-sold')->weeklyOn(0,'11:00');

        //Update friends
        //$schedule->command('app:update-twitter-followers-followings')->weeklyOn(5, '14:00');


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
