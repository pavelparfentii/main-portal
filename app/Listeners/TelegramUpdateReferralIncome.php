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

        $invitesSent = $account->invitesSent()->get();

        foreach ($invitesSent as $referral) {


            $firstLevelAccount = Account::on('pgsql_telegrams')
                ->where('id', $referral->whom_invited)
                ->first();

            if ($firstLevelAccount ) {

                $earnedPoints = DB::connection('pgsql_telegrams')
                        ->table('account_referrals')
                        ->where('account_id', $account->id)
                        ->where('ref_subref_id', $firstLevelAccount->id)
                        ->value('income') ?? 0;

                $firstLevelIncome = $account->ambassador ? ($earnedPoints) * ConstantValues::first_level_ambas_ref : ($earnedPoints) * ConstantValues::first_level_ref;


                $this->checkRelation($account, $firstLevelAccount, $firstLevelIncome, $earnedPoints);

                $secondLevelInvites = Invite::on('pgsql_telegrams')
                    ->where('invited_by', $firstLevelAccount->id)
                    ->get();



                foreach ($secondLevelInvites as $secondLevelInvite) {

                    $secondLevelAccount = Account::on('pgsql_telegrams')
                        ->where('id', $secondLevelInvite->whom_invited)
                        ->first();

                    if ($secondLevelAccount) {
                        $secondLevelEarnedPoints = DB::connection('pgsql_telegrams')
                                ->table('account_referrals')
                                ->where('account_id', $account->id)
                                ->where('ref_subref_id', $secondLevelAccount->id)
                                ->value('income') ?? 0;


                        $secondLevelIncome = $account->ambassador ? ($secondLevelEarnedPoints) * ConstantValues::second_level_ambas_ref : ($secondLevelEarnedPoints) * ConstantValues::second_level_ref;

//                        $totalSecondLevelIncome += $secondLevelIncome;

                        $this->checkRelation($account, $secondLevelAccount, $secondLevelIncome, $secondLevelEarnedPoints);

                    }
                }

            }

        }
    }


    private function checkRelation($account, $referral, $netIncome, $income)
    {
        $existingRelation = $account->refSubrefAccounts()
            ->wherePivot('ref_subref_id', $referral->id)
            ->exists();

        if ($existingRelation) {
            DB::connection('pgsql_telegrams')
                ->table('account_referrals')
                ->where('account_id', $account->id)
                ->where('ref_subref_id', $referral->id)
                ->update(['net_income'=> $netIncome, 'income'=>$income]);


        }else{

            $account->refSubrefAccounts()->syncWithoutDetaching([$referral->id => [
                'claimed_balance' => $referral->total_points,
                'net_income' => 0,
                'income' =>0
            ]]);
        }


    }
}

