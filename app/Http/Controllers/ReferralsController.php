<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Account;
use Illuminate\Http\Request;

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

        $collectReferral = $referralsList->map(function ($referral) {
            $account = Account::where('id', $referral->whom_invited)->first();
            if ($account) {
                if(!empty($account->twitter_id)){
                    return [
                        'id'=>$account->id,
                        'twitter_name' => $account->twitter_name,
                        'twitter_avatar'=>$account->twitter_avatar,
                        'twitter_username'=>$account->twitter_username,
                        'total_points' => $account->total_points,
                        'invited'=>$account->invitesSent()->count()
                    ];
                }else{
                    return [
                        'id'=>$account->id,
                        'total_points' => $account->total_points,
                        'invited'=>$account->invitesSent()->count()
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
                'twitter_username'=>$account->twitter_username,
                'total_points' => $inviteReceived->invitedBy->total_points,

            ];
        }

        return response()->json([
                'referrals' => $collectReferral,
                'invited_me' => $invitedMe,
                'invited'=>$invitedCount
        ]);

    }
}
