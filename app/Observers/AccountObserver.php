<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\AccountFarm;
use Illuminate\Support\Facades\Cache;

class AccountObserver
{
    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        AccountFarm::create([
            'account_id' => $account->id,
            'daily_farm' => 0.00, // Default value
            'last_updated' => now(), // Default value
            'total_points'=>$account->total_points,
        ]);
    }

    /**
     * Handle the Account "updated" event.
     */
    public function updated(Account $account): void
    {
        $cacheKeys = [
            "points_data_total_{$account->id}",
            "points_data_week_{$account->id}",
            "teams_list_total_{$account->id}",
            "teams_list_week_{$account->id}"
        ];

//        \Log::info('logged here');

        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }

    /**
     * Handle the Account "deleted" event.
     */
    public function deleted(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "restored" event.
     */
    public function restored(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "force deleted" event.
     */
    public function forceDeleted(Account $account): void
    {
        //
    }
}
