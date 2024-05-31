<?php

namespace App\Console\Commands;

use App\ConstantValues;
use App\Models\Account;
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
       $accounts = Account::where('daily_farm', '>', 0)->cursor();

       foreach ($accounts as $account){
           $currentWeek = Week::getCurrentWeekForAccount($account);
           $currentWeek->increment('total_points', $account->daily_farm);
       }
    }
}
