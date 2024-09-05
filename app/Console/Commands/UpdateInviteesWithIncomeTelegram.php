<?php

namespace App\Console\Commands;

use App\ConstantValues;
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
        $account = Account::on('pgsql_telegrams')->where('id', 9)->first();

        $invitesSent = $account->invitesSent()->get();


        foreach ($invitesSent as $referral) {

            $totalFirstLevelIncome = 0;
            $totalSecondLevelIncome = 0;

            $firstLevelAccount = Account::on('pgsql_telegrams')
                ->where('id', $referral->whom_invited)
                ->first();

            if ($firstLevelAccount ) {
                $earnedPoints = DB::connection('pgsql_telegrams')
                        ->table('account_farms')
                        ->where('account_id', $firstLevelAccount->id)
                        ->value('daily_farm') ?? 0;


                $firstLevelIncome = $account->ambassador ? ($earnedPoints) * ConstantValues::first_level_ambas_ref : ($earnedPoints) * ConstantValues::first_level_ref;

                $totalFirstLevelIncome += $firstLevelIncome;

                $account->refSubrefAccounts()->syncWithoutDetaching([$firstLevelAccount->id => [
                    'claimed_balance' => $firstLevelAccount->total_points,
                    'net_income' => $firstLevelIncome,
                    'income' =>$earnedPoints
                ]]);

                $secondLevelInvites = Invite::on('pgsql_telegrams')
                    ->where('invited_by', $firstLevelAccount->id)
                    ->get();



                foreach ($secondLevelInvites as $secondLevelInvite) {

                    $secondLevelAccount = Account::on('pgsql_telegrams')
                        ->where('id', $secondLevelInvite->whom_invited)
                        ->first();

                    if ($secondLevelAccount) {
                        $secondLevelEarnedPoints = DB::connection('pgsql_telegrams')
                                ->table('account_farms')
                                ->where('account_id', $secondLevelAccount->id)
                                ->value('daily_farm') ?? 0;


                        $secondLevelIncome = $account->ambassador ? ($secondLevelEarnedPoints) * ConstantValues::second_level_ambas_ref : ($secondLevelEarnedPoints) * ConstantValues::second_level_ref;

                        $totalSecondLevelIncome += $secondLevelIncome;

                        $account->refSubrefAccounts()->syncWithoutDetaching([$secondLevelAccount->id => [
                            'claimed_balance' => $secondLevelAccount->total_points,
                            'net_income' => $secondLevelIncome,
                            'income' =>$secondLevelEarnedPoints
                        ]]);
                    }
                }
                $accumulatedIncome = $totalFirstLevelIncome + $totalSecondLevelIncome;
                //$this->accumulateIncomeForInvite($referral, $accumulatedIncome);
            }

        }
//        $test = Invite::on('pgsql_telegrams')
//            ->where('invited_by', $account->id)
//            ->first();


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
