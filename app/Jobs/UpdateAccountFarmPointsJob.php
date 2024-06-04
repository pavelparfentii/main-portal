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

    public function __construct($holder)
    {
        $this->holder=$holder;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::info('farm here');
        $farmPoints = DB::table('farming_n_f_t_s')
            ->where('holder', $this->holder)
            ->sum('farm_points_daily_total');

        $account = DB::table('accounts')
            ->where('wallet', $this->holder)
            ->first(['id']);

        if($account){
            $this->updateFarmPoints($account, $farmPoints);
        }

    }

    private function updateFarmPoints($account, $farmPoints)
    {
        $accountDailyFarm = DB::table('account_farms')
            ->where('account_id', $account->id)
            ->select('daily_farm', 'daily_farm_last_update')
            ->first();
        $dailyFarmRate = $accountDailyFarm->daily_farm;
        $lastUpdated = $accountDailyFarm->daily_farm_last_update;


        $now = Carbon::now();
        $lastUpdatedTime = Carbon::parse($lastUpdated);

        $timeDifferenceInSeconds = $now->diffInSeconds($lastUpdatedTime);

        $pointsPerSecond = $dailyFarmRate / (24 * 60 * 60);

        $earnedPoints = $pointsPerSecond * $timeDifferenceInSeconds;


        DB::table('account_farms')
            ->where('account_id', $account->id)
            ->increment('total_points', $earnedPoints);

        DB::table('account_farms')
            ->where('account_id', $account->id)
            ->update(['daily_farm_last_update' => now(), 'daily_farm' => $farmPoints]);

    }
}
