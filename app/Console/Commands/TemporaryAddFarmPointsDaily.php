<?php

namespace App\Console\Commands;

use App\ConstantValues;
use App\Models\Account;
use App\Models\AccountFarm;
use App\Models\Week;
use Illuminate\Console\Command;

class TemporaryAddFarmPointsDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:temporary-add-farm-points-daily';

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
       $accountsFarm = AccountFarm::where('daily_farm', '>', 0)->pluck('account_id')->toArray();

       $accounts = Account::whereIn('id', $accountsFarm)->cursor();

       foreach ($accounts as $account){
           $currentWeek = Week::getCurrentWeekForAccount($account);

           $currentWeek->increment('farm_points', $account->dailyFarm->daily_farm);
           $currentWeek->increment('total_points', $account->dailyFarm->daily_farm);
       }
    }
}
