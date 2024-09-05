<?php

namespace App\Http\Controllers;

use App\ConstantValues;
use App\Helpers\AuthHelper;
use App\Models\Account;
use App\Models\AccountFarm;
use App\Models\Invite;
use App\Models\Week;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralsController extends Controller
{
    public function getReferralsData(Request $request)
    {
        $account = AuthHelper::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }


        $invitedCount = $account->invitesSent()->count();

        $invitesReceived = $account->invitedMe()->get();

        $referralsList = $account->invitesSent()->get();

        //how much invited second level
        // total income 1-й + 2-й левел
        // boolean пройшов  тиждень чи ні referrals_claimed

        $totalFirstLevelIncome = 0;
        $totalSecondLevelIncome = 0;

        $collectReferral = $referralsList->map(function ($referral) use (&$totalFirstLevelIncome, &$totalSecondLevelIncome) {
            $account = Account::where('id', $referral->whom_invited)->first();
            if ($account) {

                $totalFirstLevelIncome = $referral->accumulated_income;
                $totalSecondLevelIncome +=$referral->accumulated_income;
//                $dailyIncome = AccountFarm::where('account_id', $account->id)->value('daily_farm');
//                $totalFirstLevelIncome += $dailyIncome * 0.10;
//
//                // Calculate second level income (2%)
//                $secondLevelInvites = Invite::where('invited_by', $account->id)->get();
//                foreach ($secondLevelInvites as $secondLevelInvite) {
//                    $secondLevelAccount = Account::where('id', $secondLevelInvite->whom_invited)->first();
//                    if ($secondLevelAccount) {
//                        $secondLevelDailyIncome = AccountFarm::where('account_id', $secondLevelAccount->id)->sum('daily_farm');
//                        $totalSecondLevelIncome += $secondLevelDailyIncome * 0.02;
//                    }
//                }


                if(!empty($account->twitter_id)){
                    return [
                        'id'=>$account->id,
                        'twitter_name' => $account->twitter_name,
                        'twitter_avatar'=>$account->twitter_avatar,
                        'twitter_username'=>$account->twitter_username,
                        'total_points' => $account->total_points,
                        'invited'=>$account->invitesSent()->count(),
                        'referral_income'=>$totalFirstLevelIncome

                    ];
                }else{
                    return [
                        'id'=>$account->id,
                        'total_points' => $account->total_points,
                        'invited'=>$account->invitesSent()->count(),
                        'referral_income'=>$totalFirstLevelIncome
                    ];
                }
            }
        })->filter(); // Filter out any null values

        $inviteReceived = $account->invitedMe()->first();
        $invitedMe = null;

        if ($inviteReceived) {
            $invitedMe = [
                'id' => $inviteReceived->invitedBy->id,
                'twitter_name' => $inviteReceived->invitedBy->twitter_name,
                'twitter_avatar' => $inviteReceived->invitedBy->twitter_avatar,
                'twitter_username'=>$inviteReceived->invitedBy->twitter_username,
                'total_points' => $inviteReceived->invitedBy->total_points,
                'code'=>$inviteReceived->used_code

            ];
        }

        return response()->json([
            'referrals' => $collectReferral,
            'invited_me' => $invitedMe,
            'invited'=>$invitedCount,
            'referrals_claimed'=> $account->referrals_claimed,
            'next_referrals_claim'=> $account->next_referrals_claim,
            'total_referrals_income'=>$totalSecondLevelIncome,

        ]);

    }

    public function claimIncome(Request $request)
    {
        $account = AuthHelper::auth($request);

        if (!$account) {
            return response()->json(['message' => 'non authorized'], 401);
        }

        $lastClaimDate = $account->last_claim_date;
        $currentDate = now();

        if ($account->referrals_claimed && $account->next_referrals_claim > now()) {

            return response()->json(['message' => 'Already claimed'], 429);
        }

        // Check if a week has passed since the last claim
        if ($lastClaimDate && $lastClaimDate->diffInDays($currentDate) < 7) {
            return response()->json(['message' => 'You can only claim your income once a week.'], 400);
        }

        $totalIncome = Invite::where('invited_by', $account->id)
            ->sum('accumulated_income');

        $account->referrals_claimed = true;
        $account->next_referrals_claim = now()->addDays(7);

        $currentWeek = Week::getCurrentWeekForAccount($account);
        $currentWeek->increment('referrals_income', $totalIncome);
        $currentWeek->increment('total_points', $totalIncome);

        // $account->last_claim_date = $currentDate;
        $account->save();

        Invite::where('invited_by', $account->id)
            ->update(['accumulated_income' => 0.000]);

        DB::table('account_farms')
            ->where('account_id', $account->id)
            ->increment('total_points', $totalIncome);


        return response()->json(['message' => 'Income claimed successfully']);
    }
}
