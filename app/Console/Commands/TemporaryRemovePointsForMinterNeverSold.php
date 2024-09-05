<?php

namespace App\Console\Commands;

use App\ConstantValues;
use App\Models\Account;
use App\Models\DigitalAnimal;
use App\Nova\Week;
use Illuminate\Console\Command;

class TemporaryRemovePointsForMinterNeverSold extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:temporary-remove-points-for-minter-never-sold';

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
        $minterNeverSold = DigitalAnimal::where('query_param', 'like', 'minter_never_%')->get();

        foreach ($minterNeverSold as $minter){
            $account_id = $minter->account_id;


            $getRewardsForAccount = DigitalAnimal::where('account_id', $account_id)
                ->where('query_param', 'like', 'minter_never_%')
                ->orderBy('week_id')
                ->get();


            if ($getRewardsForAccount->count() > 1) {

                $getRewardsForAccount->slice(1)->each(function ($item) {
                    // Deduct points
                    $getWeek = \App\Models\Week::find($item->week_id);
                    if ($getWeek) {
                        $pointsToDeduct = -ConstantValues::minter_never_sold;
                        $getWeek->increment('points', $pointsToDeduct);
                        $getWeek->increment('total_points', $pointsToDeduct);
                    }

                    $account = Account::find($item->account_id);
                    if($account){
                        $pointsToDeduct = -ConstantValues::minter_never_sold;
                        $account->increment('total_points', $pointsToDeduct);
                    }
                    $item->delete();
                });
            }

            if ($getRewardsForAccount->isNotEmpty()) {
                $remainingItem = $getRewardsForAccount->first();
                // Perform your update operations on $remainingItem here
                // For example:
                $remainingItem->query_param = 'minter_never_sold';
                $remainingItem->save();
            }
        }
    }
}
