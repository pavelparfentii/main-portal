<?php

namespace App\Observers;

use App\ConstantValues;
use App\Models\Account;
use App\Models\DigitalAnimal;
use App\Models\Week;
use Illuminate\Database\Eloquent\Model;

class AccountTelegramObserver
{
    public function created(Account $account): void
    {
        $lowestRank = Account::on('pgsql_telegrams')->max('current_rank') ?? null;
        $previousLowestRank = Account::on('pgsql_telegrams')->max('previous_rank') ?? null;

        // Assign the rank to the new account
        $account->current_rank = $lowestRank;
        $account->previous_rank = $previousLowestRank;

        $account->save();

        $currentWeek = Week::on('pgsql_telegrams')->getCurrentWeekForAccount($account);
        $telegram_id = $account->telegram->telegram_id;
        if(!empty($telegram_id)){
            $queryParam = 'telegram_connect';

            $existingTelegram = DigitalAnimal::on('pgsql_telegrams')->where('query_param', $queryParam)
                ->where('account_id', $account->id)
                ->first();

            if(!$existingTelegram){
                $newAnimal = new DigitalAnimal([
                    'account_id' => $account->id,
                    'points' => ConstantValues::telegram_connect,
                    'comment' => 'telegram_connect',
                    'query_param' => $queryParam
                ]);

                $newAnimal->setConnection('pgsql_telegrams');

                $newAnimal->save();

                $currentWeek->animals()->save($newAnimal);
                $currentWeek->increment('total_points', ConstantValues::telegram_connect);
                $currentWeek->increment('points', ConstantValues::telegram_connect);
            }
        }
    }
}
