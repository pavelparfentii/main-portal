<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountFarm;
use App\Models\Task;
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
            $accountDailyFarm = DB::connection('pgsql_telegrams')
                ->table('account_farms')
                ->where('account_id', $account->id)
                ->value('daily_farm');

            $claimedIncome = floatval($account->total_points)-floatval($accountDailyFarm);

              $accountDailyFarm = DB::connection('pgsql_telegrams')
                ->table('account_farms')
                ->where('account_id', $account->id)
                ->update(['total_points'=>$claimedIncome]);
        }

//        $startOfDay = Carbon::today()->startOfDay();
//        $endOfDay = Carbon::today()->endOfDay();
//
//        // Виконуємо запит для отримання всіх користувачів, створених сьогодні
//        $accounts = Account::on('pgsql_telegrams')->whereBetween('created_at', [$startOfDay, $endOfDay])->get();
////
////        $accounts = Account::on('pgsql_telegrams')->cursor();
////
////        $tasks = Task::on('pgsql_telegrams')->cursor();
//
//        foreach ($accounts as $account){
//            $accountDailyFarm = DB::connection('pgsql_telegrams')
//                ->table('account_farms')
//                ->where('account_id', $account->id)
//                ->update(['daily_farm'=>$account->total_balance]);
//        }



//        foreach ($accounts as $account){
//
//            foreach ($tasks as $task) {
//                $account->tasks()->attach($task->id, ['is_done' => false]);
//            }
////            $farm = AccountFarm::on('pgsql_telegrams')
////                ->where('account_id', $account->id)
////                ->first();
////
////            if($farm){
////                $account->total_points = $farm->total_points;
////                $account->save();
////            }
//        }



//        foreach ($accounts as $account){
//            $this->checkDailyPoints($account);
//
//
//        }
        //
    }

    private function checkDailyPoints($account)
    {
//        $currentWeek = Week::getCurrentWeekForTelegramAccount($account);
//
//        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
//        // Отримуємо очки користувача за поточний тиждень
//        $previousUserWeekPoints = $account->weeks()
//                ->where('week_number', $previousWeekNumber)
//                ->where('active', false) // Враховуємо активні тижні, якщо потрібно
//                ->first()
//                ->total_points ?? 0;
//
//        $earnedPoints = $currentWeek->total_points + $previousUserWeekPoints;
//
//        $accountDailyFarm = DB::connection('pgsql_telegrams')
//            ->table('account_farms')
//            ->where('account_id', $account->id)
//            ->first();
//
//        if (!$accountDailyFarm) {
//            DB::connection('pgsql_telegrams')
//                ->table('account_farms')
//                ->insert([
//                'account_id' => $account->id,
//                'daily_farm' => $currentWeek->total_points, // Set default value for daily_farm
//                'daily_farm_last_update' => now(), // Set default value for daily_farm_last_update
//                'total_points' => $earnedPoints,
//                'created_at' => now(),
//                'updated_at' => now(),
//            ]);
//
//            $accountDailyFarm = DB::connection('pgsql_telegrams')
//                ->table('account_farms')
//                ->where('account_id', $account->id)
//                ->first();
//
//
//        }else{
//            DB::connection('pgsql_telegrams')
//                ->table('account_farms')
//            ->where('account_id', $account->id)
//            ->update(['total_points'=> $earnedPoints, 'daily_farm'=>$currentWeek->total_points]);
//        }




    }
}
