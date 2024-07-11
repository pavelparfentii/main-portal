<?php

namespace App\Listeners;

use App\ConstantValues;
use App\Events\TelegramPointsUpdated;
use App\Models\Account;
use App\Models\Invite;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class TelegramUpdateReferralIncome
{
    /**
     * Create the event listener.
     */
    public function handle(TelegramPointsUpdated $event)
    {
        $account = $event->account;

        $nextReferralsClaim = $account->next_referrals_claim ?? now()->addDays(1);
        $account->save();
        if (empty($account->next_referrals_claim)) {
            $account->next_referrals_claim = now()->addDays(1);
            $account->save();
        }

        $invitesSent = $account->invitesSent()->get();

        foreach ($invitesSent as $referral) {
            $firstLevelAccount = Account::on('pgsql_telegrams')
                ->where('id', $referral->whom_invited)
                ->first();

            $update = $referral->next_update_date;

            $totalFirstLevelIncome = 0;
            $totalSecondLevelIncome = 0;

            if ($firstLevelAccount ) {
                $earnedPoints = DB::connection('pgsql_telegrams')
                        ->table('account_farms')
                        ->where('account_id', $firstLevelAccount->id)
                        ->value('daily_farm') ?? 0.0;

                $firstLevelIncome = $account->ambassador ? ($earnedPoints) * ConstantValues::first_level_ambas_ref : ($earnedPoints) * ConstantValues::first_level_ref;

                $totalFirstLevelIncome += $firstLevelIncome;

                $secondLevelInvites = Invite::on('pgsql_telegrams')
                    ->where('invited_by', $firstLevelAccount->id)
                    ->get();
                foreach ($secondLevelInvites as $secondLevelInvite) {
                    $secondLevelAccount = Account::on('pgsql_telegrams')
                        ->where('id', $secondLevelInvite->whom_invited)
                        ->first();
                    if ($secondLevelAccount) {
                        $earnedPoints = DB::connection('pgsql_telegrams')
                                ->table('account_farms')
                                ->where('account_id', $secondLevelAccount->id)
                                ->value('daily_farm') ?? 0.0;

                        $secondLevelIncome = $account->ambassador ? ($earnedPoints) * ConstantValues::second_level_ambas_ref : ($earnedPoints) * ConstantValues::second_level_ref;

                        $totalSecondLevelIncome += $secondLevelIncome;
                    }
                }
            }
            $accumulatedIncome = $totalFirstLevelIncome + $totalSecondLevelIncome;

            $this->accumulateIncomeForInvite($referral, $accumulatedIncome);
        }
    }

    private function accumulateIncomeForInvite($invite, $income)
    {
        $lastUpdate = $invite->next_update_date ? now() : null;
        $today = now()->toDateString();

        $invite->accumulated_income = $income;
        $invite->save();

        if ($lastUpdate != $today) {

            $invite->next_update_date = now();
            $invite->save();
        }
    }
}
