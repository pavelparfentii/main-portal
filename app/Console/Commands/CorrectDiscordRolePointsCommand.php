<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\DigitalAnimal;
use App\Models\Week;
use Illuminate\Console\Command;

class CorrectDiscordRolePointsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:correct-discord-role-points-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $discordRoles = DigitalAnimal::where('query_param', 'like', 'discord_%')->get();

        foreach ($discordRoles as $discordRole){
           $week = Week::where('id', $discordRole->week_id)->first();
           $account = Account::where('id', $discordRole->account_id)->first();
            if($discordRole->week_id == $week->id){
                $week->decrement('points', 5.000);
                $week->decrement('total_points', 5.000);
            }

            if($discordRole->account_id == $account->id){

                $account->decrement('total_points', 5.000);
            }

            $discordRole->update(['points'=>5.000]);
        }
    }
}
