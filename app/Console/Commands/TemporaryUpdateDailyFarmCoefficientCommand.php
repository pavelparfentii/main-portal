<?php

namespace App\Console\Commands;

use App\ConstantValues;
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
        $accounts = Account::cursor();

//        $accounts = Account::whereNotNull('wallet')->

        $accounts = DB::table('accounts')
            ->join('account_farms', 'accounts.id', '=', 'account_farms.account_id')
            ->whereNotNull('accounts.wallet')
            ->where('account_farms.daily_farm', '=', 0)
            ->get();
//        dd($accounts);


//        DB::table('account_farms')->truncate();

        foreach ($accounts as $account) {

            $accountExistsNFT = DB::table('farming_n_f_t_s')
                ->where('holder', $account->wallet)
                ->exists();

            $lordPoints = DB::table('farming_n_f_t_s')
                ->where('holder', $account->wallet)
                ->whereNull('token_id')
                ->where('token_balance', '>=', 20)
                ->exists();


            $accountExistsDiscord = DB::table('farming_discords')
                ->where('discord_id', $account->discord_id)
                ->exists();


            $farmPoints = 0;


            if ($accountExistsNFT) {
                $farmPointsNFT = DB::table('farming_n_f_t_s')
                    ->where('holder', $account->wallet)
                    ->sum('farm_points_daily_total');
                $farmPoints += $farmPointsNFT;
            }


            if ($accountExistsDiscord) {
                $farmPointsDiscord = DB::table('farming_discords')
                    ->where('discord_id', $account->discord_id)
                    ->sum('item_points_daily');
                $farmPoints += $farmPointsDiscord;
            }

            if($lordPoints){
                $farmPointsLord = DB::table('farming_roles')
                    ->where('name', 'Lord')
                    ->value('daily_farm');
                $farmPoints += $farmPointsLord;

            }

            DB::table('account_farms')
                ->where('account_id', $account->id,)
                ->update([
                    'daily_farm' => $farmPoints,
                    'daily_farm_last_update' => now(),
                    'total_points' => $account->total_points,
                    'lord_points_applied'=> (bool)$lordPoints
                ]);


//            DB::table('account_farms')->insert([
//                'account_id' => $account->id,
//                'daily_farm' => $farmPoints,
//                'daily_farm_last_update' => now(),
//                'total_points' => $account->total_points,
//                'lord_points_applied'=> (bool)$lordPoints
//            ]);

        }
        $this->info('success');

    }
}
