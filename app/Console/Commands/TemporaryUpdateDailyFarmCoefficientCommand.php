<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TemporaryUpdateDailyFarmCoefficientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:temporary-update-daily-farm-coefficient-command';

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
        $accounts = Account::whereNotNull('wallet')->cursor();

        foreach ($accounts as $account){
            $accountExists = DB::table('farming_n_f_t_s')
                ->where('holder', $account->wallet)
                ->exists();
            if($accountExists){
                $farmPoints = DB::table('farming_n_f_t_s')
                    ->where('holder', $account->wallet)
                    ->sum('farm_points_daily_total');

                DB::table('accounts')
                    ->where('wallet', $account->wallet)
                    ->update(['daily_farm' => $farmPoints]);
            }
        }
        $this->info('success');
    }
}
