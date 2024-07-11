<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Invite;
use App\Models\Week;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateInviteesWithIncomeTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-invitees-with-income-telegram';

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
        $accounts = Account::on('pgsql_telegrams')->cursor();

        foreach ($accounts as $account) {

//            $nextReferralsClaim = $account->next_referrals_claim ?? now()->addDays(1);
//            $nextReferralsClaim = now()->addDays(1);
            $nextReferralsClaim = $account->next_referrals_claim;
            if (empty($account->next_referrals_claim)) {
                $account->next_referrals_claim = now()->addDays(1);
                $account->save();
            }

            $invitesSent = $account->invitesSent()->get();


            foreach ($invitesSent as $referral) {
                $firstLevelAccount = Account::on('pgsql_telegrams')
                    ->where('id', $referral->whom_invited)
                    ->first();
//                $update = $firstLevelAccount->next_update_date;
                $update = $referral->next_update_date;

                $totalFirstLevelIncome = 0;
                $totalSecondLevelIncome = 0;
                // var_dump($update);

                if ($firstLevelAccount && ($nextReferralsClaim >$update)) {
//                    var_dump('here');
//                    $currentWeek = Week::getCurrentWeekForTelegramAccount($firstLevelAccount);

                    $earnedPoints = DB::connection('pgsql_telegrams')
                            ->table('account_farms')
                            ->where('account_id', $firstLevelAccount->id)
                            ->value('daily_farm') ?? 0.0;

//                    $currentWeekIncome = $currentWeek->points + $currentWeek->claimed_points;


                    $firstLevelIncome = $account->ambassador ? ($earnedPoints) * 0.20 : ($earnedPoints) * 0.10;

                    $totalFirstLevelIncome += $firstLevelIncome;


//                    $this->accumulateIncomeForInvite($referral, $firstLevelIncome);

                    // Calculate second level income (2%)
                    $secondLevelInvites = Invite::on('pgsql_telegrams')->where('invited_by', $firstLevelAccount->id)->get();
                    foreach ($secondLevelInvites as $secondLevelInvite) {
                        $secondLevelAccount = Account::on('pgsql_telegrams')->where('id', $secondLevelInvite->whom_invited)->first();
                        if ($secondLevelAccount) {

//                            $currentWeek = Week::getCurrentWeekForTelegramAccount($secondLevelAccount);
//                            $currentWeekIncome = $currentWeek->points + $currentWeek->claimed_points;
                            $earnedPoints = DB::connection('pgsql_telegrams')
                                    ->table('account_farms')
                                    ->where('account_id', $secondLevelAccount->id)
                                    ->value('daily_farm') ?? 0.0;

                            $secondLevelIncome = $account->ambassador ? ($earnedPoints) * 0.05 : ($earnedPoints) * 0.02;

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

//        if ($lastUpdate && now()->diffInDays($invite->next_update_date) >= 7) {
//            $invite->accumulated_income = 0;
//        }

        if ($lastUpdate != $today) {
            $invite->accumulated_income = $income;
            $invite->next_update_date = now();
            $invite->save();
        }
    }
}
