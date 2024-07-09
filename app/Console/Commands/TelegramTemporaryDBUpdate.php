<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TelegramTemporaryDBUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:telegram-temporary-d-b-update';

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
        $accounts = Account::on('pgsql_telegrams')->cursor();

        foreach ($accounts as $account){
            $this->checkDailyPoints($account);


        }
        //
    }

    private function checkDailyPoints($account)
    {
        $currentWeek = Week::getCurrentWeekForTelegramAccount($account);

        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
        // Отримуємо очки користувача за поточний тиждень
        $previousUserWeekPoints = $account->weeks()
                ->where('week_number', $previousWeekNumber)
                ->where('active', false) // Враховуємо активні тижні, якщо потрібно
                ->first()
                ->total_points ?? 0;

        $earnedPoints = $currentWeek->total_points + $previousUserWeekPoints;

        $accountDailyFarm = DB::connection('pgsql_telegrams')
            ->table('account_farms')
            ->where('account_id', $account->id)
            ->first();

        if (!$accountDailyFarm) {
            DB::connection('pgsql_telegrams')
                ->table('account_farms')
                ->insert([
                'account_id' => $account->id,
                'daily_farm' => $currentWeek->total_points, // Set default value for daily_farm
                'daily_farm_last_update' => now(), // Set default value for daily_farm_last_update
                'total_points' => $earnedPoints,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $accountDailyFarm = DB::connection('pgsql_telegrams')
                ->table('account_farms')
                ->where('account_id', $account->id)
                ->first();


        }else{
            DB::connection('pgsql_telegrams')
                ->table('account_farms')
            ->where('account_id', $account->id)
            ->update(['total_points'=> $earnedPoints, 'daily_farm'=>$currentWeek->total_points]);
        }




    }
}
