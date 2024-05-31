<?php

namespace App\Jobs;

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


        $accountExists = DB::table('accounts')
            ->where('wallet', $this->holder)
            ->exists();

        if ($accountExists) {
            DB::table('accounts')
                ->where('wallet', $this->holder)
                ->update(['daily_farm' => $farmPoints]);
        }

//        $totalPoints = FarmingNFT::where('holder', $this->holder)->sum('farm_points_daily_total');
//        $account = Account::where('wallet', $this->holder)->first();
//        if ($account) {
//            $account->update(['farm_points_daily_total' => $totalPoints]);
//        }
    }
}
