<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountFarm;
use App\Models\Invite;
use Illuminate\Console\Command;

class UpdateInviteesWithIncome extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-invitees-with-income';

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
        $accounts = Account::cursor();

        foreach ($accounts as $account) {

            $nextReferralsClaim = $account->next_referrals_claim ?? now()->addDays(7);
            $account->next_referrals_claim = $nextReferralsClaim;
            $account->save();
            $totalFirstLevelIncome = 0;
            $totalSecondLevelIncome = 0;

            $invitesSent = $account->invitesSent()->get();

            foreach ($invitesSent as $referral) {
                $firstLevelAccount = Account::where('id', $referral->whom_invited)->first();
//                $update = $firstLevelAccount->next_update_date;
                $update = $referral->next_update_date;
                var_dump($update);

                if ($firstLevelAccount && ($nextReferralsClaim >$update)) {
                    var_dump('here');
                    $dailyIncome = AccountFarm::where('account_id', $firstLevelAccount->id)->value('daily_farm');
                    $firstLevelIncome = $dailyIncome * 0.10;
                    $totalFirstLevelIncome += $firstLevelIncome;
                    var_dump($firstLevelIncome);

                    $this->accumulateIncomeForInvite($referral, $firstLevelIncome);

                    // Calculate second level income (2%)
                    $secondLevelInvites = Invite::where('invited_by', $firstLevelAccount->id)->get();
                    foreach ($secondLevelInvites as $secondLevelInvite) {
                        $secondLevelAccount = Account::where('id', $secondLevelInvite->whom_invited)->first();
                        if ($secondLevelAccount) {
                            $secondLevelDailyIncome = AccountFarm::where('account_id', $secondLevelAccount->id)->value('daily_farm');
                            $secondLevelIncome = $secondLevelDailyIncome * 0.02;
                            var_dump($secondLevelIncome);
                            $totalSecondLevelIncome += $secondLevelIncome;

                            //$this->accumulateIncomeForInvite($secondLevelInvite, $secondLevelIncome);
                        }
                    }
                }
            }

//            // Optionally update account's total accumulated income if needed
//            $account->accumulated_income = $totalFirstLevelIncome + $totalSecondLevelIncome;
//            $account->save();
        }



    }

    private function accumulateIncomeForInvite($invite, $income)
    {
        $lastUpdate = $invite->next_update_date ? now() : null;
        $today = now()->toDateString();

        if ($lastUpdate && now()->diffInDays($invite->next_update_date) >= 7) {
            $invite->accumulated_income = 0;
        }

        if ($lastUpdate != $today) {
            $invite->accumulated_income += $income;
            $invite->next_update_date = now();
            $invite->save();
        }
    }

}
