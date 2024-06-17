<?php

namespace App\Observers;

use App\ConstantValues;
use App\Models\Account;
use App\Models\AccountFarm;
use App\Models\DigitalAnimal;
use App\Models\Twitter;
use App\Models\Week;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            'daily_farm_last_update' => now(), // Default value
            'total_points'=>ConstantValues::souls_connect,
        ]);

        $currentWeek = Week::getCurrentWeekForAccount($account);


        $currentWeek->increment('total_points', ConstantValues::souls_connect);

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

        $currentWeek = Week::getCurrentWeekForAccount($account);

        if (!empty($account->twitter_id)){
            $queryParam = 'twitter_connect';

            $existingTwitter = Twitter::where('query_param', $queryParam)
                ->where('account_id', $account->id)
                ->first();

            if(!$existingTwitter){
                $twitter = new Twitter([
                    'account_id'=>$account->id,
                    'points' => ConstantValues::x_connect,
                    'comment' => 'Connect X',
                    'query_param' => $queryParam
                ]);
                $currentWeek->twitters()->save($twitter);
                $currentWeek->increment('total_points', ConstantValues::x_connect);
                $currentWeek->increment('points', ConstantValues::x_connect);

                DB::table('account_farms')
                    ->where('account_id', $account->id)
                    ->increment('total_points', ConstantValues::x_connect);
            }
        }

        if(!empty($account->discord_id)){
            $queryParam = 'discord_connect';

            $existingTwitter = DigitalAnimal::where('query_param', $queryParam)
                ->where('account_id', $account->id)
                ->first();

            if(!$existingTwitter){
                $newAnimal = new DigitalAnimal([
                    'account_id' => $account->id,
                    'points' => ConstantValues::discord_connect,
                    'comment' => 'discord_connect',
                    'query_param' => $queryParam
                ]);
                $currentWeek->animals()->save($newAnimal);
                $currentWeek->increment('total_points', ConstantValues::discord_connect);
                $currentWeek->increment('points', ConstantValues::discord_connect);

                DB::table('account_farms')
                    ->where('account_id', $account->id)
                    ->increment('total_points', ConstantValues::discord_connect);
            }
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
