<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ChangeWeekCalculatePoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-week-calculate-points';

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
//        $currentWeekNumber = Carbon::now()->format('W-Y');
        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');


        Week::where('week_number', $previousWeekNumber)->update(['active'=>false]);

//        $accounts = Account::cursor();
//        foreach ($accounts as $account){
//            $previousWeek = Week::where('account_id', $account->id)
//                ->where('week_number', $previousWeekNumber)
//                ->where('active', true)
//                ->first();
//            if ($previousWeek) { // Check if $previousWeek is not null
//                $account->total_points += $previousWeek->points ?? 0.000;
//                $account->save();
//
//                $previousWeek->update(['active' => false]);
//            }
//        }
    }
}
