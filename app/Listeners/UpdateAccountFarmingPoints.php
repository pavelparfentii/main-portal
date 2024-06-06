<?php

namespace App\Listeners;

use App\Events\FarmingNFTUpdated;
use App\Jobs\UpdateAccountFarmPointsJob;
use App\Models\FarmingNFT;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateAccountFarmingPoints
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(FarmingNFTUpdated $event): void
    {
        Log::info('Handling FarmingNFTUpdated event');
        Log::info('farmingNFTId: ' . $event->farmingNFTId);
        Log::info('accountId: ' . $event->accountId);
        if(isset($event->farmingNFTId)){
            $farmingNFT = FarmingNFT::where('id', $event->farmingNFTId)->first();
        }else{
            $farmingNFT = null;
        }


        if($farmingNFT){
            $accountExists = DB::table('accounts')
                ->where('wallet', $farmingNFT->holder)
                ->exists();

            if ($farmingNFT->token_balance <= 0) {
                if ($accountExists) {
                    DB::table('farming_n_f_t_s')
                        ->where('id', $farmingNFT->id)
                        ->update([
                            'token_balance_last_update' => now(),
                            'item_points_daily' => $farmingNFT->item_points_daily,
                            'farm_points_daily_total' => 0,
                        ]);
                } else {
                    DB::table('farming_n_f_t_s')
                        ->where('id', $farmingNFT->id)
                        ->delete();
                }
            }else {
                DB::table('farming_n_f_t_s')
                    ->where('id', $farmingNFT->id)
                    ->update([
                        'token_balance_last_update' => now(),
                        'item_points_daily' => $farmingNFT->item_points_daily,
                        'farm_points_daily_total' => round($farmingNFT->token_balance * $farmingNFT->item_points_daily, 3),
                    ]);
            }

            UpdateAccountFarmPointsJob::dispatch($farmingNFT->holder, null)->onQueue('farm');
        }else{
            UpdateAccountFarmPointsJob::dispatch(null, $event->accountId)->onQueue('farm');
        }

    }
}
