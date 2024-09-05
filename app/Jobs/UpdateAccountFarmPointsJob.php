<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateAccountFarmPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $holder;
    protected $id;
    protected $roleAction;

    public function __construct($holder = null, $id = null, $roleAction= null)
    {
        $this->holder=$holder;
        $this->id = $id;
        $this->roleAction = $roleAction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::info('farm here');
        $farmPoints = DB::table('farming_n_f_t_s')
            ->where('holder', $this->holder)
            ->sum('farm_points_daily_total') ?? 0;

        if(isset($this->holder)){
            Log::info('holder'. $this->holder);
            $account = DB::table('accounts')
                ->where('wallet', $this->holder)
                ->first(['id', 'discord_id']);
            if($account){
                $this->updateFarmPoints($account, $farmPoints);
            }
        }elseif(isset($this->id)){
            $account = DB::table('accounts')
                ->where('id', $this->id)
                ->first(['id', 'discord_id']);

            $accountDailyFarm = DB::table('account_farms')
                ->where('account_id', $account->id)
                ->select('daily_farm')
                ->first();

            if(isset($this->roleAction) && $this->roleAction === 'addRole'){
                $farmPoints = $accountDailyFarm->daily_farm += 0.143;
            }else{
                $farmPoints = $accountDailyFarm->daily_farm -= 0.143;
            }

            if($account){
                $this->updateFarmPoints($account, $farmPoints);
            }
        }




    }

    private function updateFarmPoints($account, $farmPoints)
    {
        Log::info('entered here');
        $accountDailyFarm = DB::table('account_farms')
            ->where('account_id', $account->id)
            ->select('daily_farm', 'daily_farm_last_update', 'total_points', 'lord_points_applied')
            ->first();

        if (!$accountDailyFarm) {
            // Handle case where account_farms entry does not exist
            DB::table('account_farms')->insert([
                'account_id' => $account->id,
                'daily_farm' => $farmPoints,
                'daily_farm_last_update' => now(),
                'total_points' => 0,
                'lord_points_applied' => false,
            ]);
            $accountDailyFarm = DB::table('account_farms')
                ->where('account_id', $account->id)
                ->select('daily_farm', 'daily_farm_last_update', 'total_points', 'lord_points_applied')
                ->first();
        }

        $dailyFarmRate = $accountDailyFarm->daily_farm;
        $lastUpdated = $accountDailyFarm->daily_farm_last_update;


        $now = Carbon::now();
        $lastUpdatedTime = Carbon::parse($lastUpdated);

        $timeDifferenceInSeconds = $now->diffInSeconds($lastUpdatedTime);

        $pointsPerSecond = $dailyFarmRate / (24 * 60 * 60);

        $earnedPoints = $pointsPerSecond * $timeDifferenceInSeconds;

        $lordRoleExists = DB::table('farming_n_f_t_s')
            ->where('holder', $this->holder)
            ->whereNull('token_id')
            ->where('token_balance', '>=', 20)
            ->exists();

        $additionalFarmPoints = 0;
        if ($lordRoleExists && !$accountDailyFarm->lord_points_applied) {

            $additionalFarmPoints = DB::table('farming_roles')
                ->where('name', 'Lord')
                ->value('daily_farm');
            DB::table('account_farms')
                ->where('account_id', $account->id)
                ->update(['lord_points_applied' => true]);

        } elseif (!$lordRoleExists && $accountDailyFarm->lord_points_applied) {

            $additionalFarmPoints = -DB::table('farming_roles')
                ->where('name', 'Lord')
                ->value('daily_farm');
            DB::table('account_farms')
                ->where('account_id', $account->id)
                ->update(['lord_points_applied' => false]);

        }

        // Calculate the difference
//        $discordFarmPoints = DB::table('farming_discords')
//            ->where('discord_id', $account->discord_id)
//            ->sum('item_points_daily');

//        Log::info($farmPoints);
//        Log::info($additionalFarmPoints);
//        Log::info($discordFarmPoints);

        $currentDailyFarm = $farmPoints + $additionalFarmPoints;


        DB::table('account_farms')
            ->where('account_id', $account->id)
            ->increment('total_points', $earnedPoints);

        DB::table('account_farms')
            ->where('account_id', $account->id)
            ->update(['daily_farm_last_update' => now(), 'daily_farm' => $currentDailyFarm]);

    }

//    public function handle(): void
//    {
//        Log::info('farm here');
//
//        $farmPointsNFT = DB::table('farming_n_f_t_s')
//            ->where('holder', $this->holder)
//            ->sum('farm_points_daily_total');
//        Log::info('holder'. $this->holder);
//        Log::info($farmPointsNFT);
//
//        $account = DB::table('accounts')
////            ->where('wallet', $this->holder)
//                ->where('id', $this->id)
//            ->first(['id', 'discord_id']);
//
//        if ($account) {
//            Log::info('account');
//            $this->updateFarmPoints($account, $farmPointsNFT);
//        }
//    }
//
//    private function updateFarmPoints($account, $farmPointsNFT)
//    {
//        $accountDailyFarm = DB::table('account_farms')
//            ->where('account_id', $account->id)
//            ->select('daily_farm', 'daily_farm_last_update', 'total_points')
//            ->first();
//        Log::info('entered update');
//        if (!$accountDailyFarm) {
//            // Handle case where account_farms entry does not exist
//            DB::table('account_farms')->insert([
//                'account_id' => $account->id,
//                'daily_farm' => $farmPointsNFT,
//                'daily_farm_last_update' => now(),
//                'total_points' => 0,
//            ]);
//            $accountDailyFarm = DB::table('account_farms')
//                ->where('account_id', $account->id)
//                ->select('daily_farm', 'daily_farm_last_update', 'total_points')
//                ->first();
//        }
//
//        $dailyFarmRate = $accountDailyFarm->daily_farm;
//        $lastUpdated = $accountDailyFarm->daily_farm_last_update;
//
//        $now = Carbon::now();
//        $lastUpdatedTime = Carbon::parse($lastUpdated);
//        $timeDifferenceInSeconds = $now->diffInSeconds($lastUpdatedTime);
//        $pointsPerSecond = $dailyFarmRate / (24 * 60 * 60);
//        $earnedPoints = $pointsPerSecond * $timeDifferenceInSeconds;
//
//        // Calculate additional farm points for Lord role
//        $additionalFarmPoints = 0;
//        $lordRoleExists = DB::table('farming_n_f_t_s')
//            ->where('holder', $this->holder)
//            ->whereNull('token_id')
//            ->where('token_balance', '>=', 20)
//            ->exists();
//
//        if ($lordRoleExists) {
//            $additionalFarmPoints = DB::table('farming_roles')
//                ->where('name', 'Lord')
//                ->value('daily_farm');
//        }
//
//        // Sum of discord farm points; defaults to 0 if no matching entries
//        $discordFarmPoints = DB::table('farming_discords')
//            ->where('discord_id', $account->discord_id)
//            ->sum('item_points_daily');
//
//        Log::info($farmPointsNFT);
//        Log::info($additionalFarmPoints);
//        Log::info($discordFarmPoints);
//
//        $currentDailyFarm = $farmPointsNFT + $additionalFarmPoints + $discordFarmPoints;
//
//        Log::info($currentDailyFarm);
//
//        // Update total_points with earned points
//        DB::table('account_farms')
//            ->where('account_id', $account->id)
//            ->increment('total_points', $earnedPoints);
//
//        Log::info($account->id);
//
//        // Update daily_farm and daily_farm_last_update
//        DB::table('account_farms')
//            ->where('account_id', $account->id)
//            ->update([
//                'daily_farm' => $currentDailyFarm,
//                'daily_farm_last_update' => now(),
//            ]);
//    }
}
