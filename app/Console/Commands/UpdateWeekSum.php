<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Week;
use Illuminate\Console\Command;

class UpdateWeekSum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-week-sum';

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
        $week11 = '11-2024';
        $week12 = '12-2024';
        $week13 = '13-2024';

        $accounts = Account::cursor();

        foreach ($accounts as $account){

            $Week11 = Week::where('account_id', $account->id)
                ->where('week_number', '=', $week11)
                ->first();
            $Week12 = Week::where('account_id', $account->id)
                ->where('week_number', '=', $week12)
                ->first();
            $Week13 = Week::where('account_id', $account->id)
                ->where('week_number', '=', $week13)
                ->first();

            if($Week11){
                $da11 = $Week11->animals()->sum('points');
                $ss11 = $Week11->safeSouls()->sum('points');

                $Week11->update(['points'=>$da11 + $ss11]);


            }if($Week12){
                $da12= $Week12->animals()->sum('points');
                $ss12= $Week12->safeSouls()->sum('points');

                $Week12->update(['points'=>$da12 + $ss12]);
            }


        }
    }
}
