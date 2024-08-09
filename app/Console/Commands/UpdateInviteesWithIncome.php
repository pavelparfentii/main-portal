<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountFarm;
use App\Models\Invite;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

            $nextReferralsClaim = Carbon::parse($account->next_referrals_claim);
//            if (!is_null($account->next_referrals_claim)) {
//                try {
//
//
//                    if ($nextReferralsClaim && now()->diffInDays($nextReferralsClaim) >= 7) {
//                        $account->next_referrals_claim = now()->addDays(7);
//                        $account->save();
//                    }
//                } catch (\Exception $e) {
//                    // Log or handle the exception if parsing fails
//                    Log::error('Failed to parse next_referrals_claim for account ID: ' . $account->id . '. Setting a default value.');
//
//                    // Optionally set a default value if parsing fails
//                    $account->next_referrals_claim = now()->addDays(7);
//                    $account->save();
//                }
//            } else {
//                // If next_referrals_claim is null, set it to now plus 7 days
//                $account->next_referrals_claim = now()->addDays(7);
//                $account->save();
//            }
//            $nextReferralsClaim = now()->addDays(1);


            $invitesSent = $account->invitesSent()->get();


            foreach ($invitesSent as $referral) {
                $firstLevelAccount = Account::where('id', $referral->whom_invited)->first();
//                $update = $firstLevelAccount->next_update_date;
                $update = $referral->next_update_date;

                $totalFirstLevelIncome = 0;
                $totalSecondLevelIncome = 0;
                // var_dump($update);

                if ($firstLevelAccount && ($nextReferralsClaim >$update)) {
//                    var_dump('here');
                    $currentWeek = Week::getCurrentWeekForAccount($firstLevelAccount);

                    $currentWeekIncome = $currentWeek->points + $currentWeek->claimed_points;


                    $dailyIncome = AccountFarm::where('account_id', $firstLevelAccount->id)->value('daily_farm');
                    // var_dump($dailyIncome);
                    $firstLevelIncome = $account->ambassador ? ($dailyIncome + $currentWeekIncome) * 0.20 : ($dailyIncome + $currentWeekIncome) * 0.10;
                    var_dump($firstLevelIncome);
                    $totalFirstLevelIncome += $firstLevelIncome;


//                    $this->accumulateIncomeForInvite($referral, $firstLevelIncome);

                    // Calculate second level income (2%)
                    $secondLevelInvites = Invite::where('invited_by', $firstLevelAccount->id)->get();
                    foreach ($secondLevelInvites as $secondLevelInvite) {
                        $secondLevelAccount = Account::where('id', $secondLevelInvite->whom_invited)->first();
                        if ($secondLevelAccount) {
                            $secondLevelDailyIncome = AccountFarm::where('account_id', $secondLevelAccount->id)->value('daily_farm');
                            $currentWeek = Week::getCurrentWeekForAccount($secondLevelAccount);
                            $currentWeekIncome = $currentWeek->points + $currentWeek->claimed_points;
//                            $currentWeekIncome = 0;

                            $secondLevelIncome = $account->ambassador ? ($secondLevelDailyIncome + $currentWeekIncome) * 0.05 : ($secondLevelDailyIncome + $currentWeekIncome) * 0.02;
                            var_dump($secondLevelIncome);
                            $totalSecondLevelIncome += $secondLevelIncome;

//                            $this->accumulateIncomeForInvite($secondLevelInvite, $secondLevelIncome);
                        }
                    }
                }
                $accumulatedIncome = $totalFirstLevelIncome + $totalSecondLevelIncome;

                $this->accumulateIncomeForInvite($referral, $accumulatedIncome);
            }

//            // Optionally update account's total accumulated income if needed

            ;
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
