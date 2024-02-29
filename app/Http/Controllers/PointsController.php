<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointsController extends Controller
{

    public function getPointsData(Request $request)
    {
        $account = AuthHelper::auth($request);
        $topAccounts = DB::table('accounts')
            ->select('id', 'wallet', 'twitter_username', 'total_points', 'twitter_name', 'twitter_avatar')
            ->orderByDesc('total_points')
            ->take(100)
            ->get();

        // Add ranks and a flag to indicate the current user
        foreach ($topAccounts as $index => $acc) {
            $acc->rank = $index + 1;
            $acc->current_user = $account && $account->id == $acc->id;
        }


        if ($account) {
            $userInTop = $topAccounts->contains('id', $account->id);

            if (!$userInTop) {
                $userRank = DB::table('accounts')
                        ->where('total_points', '>', $account->total_points)
                        ->count() + 1;

                $accountData = [
                    'id' => $account->id,
                    'wallet' => $account->wallet,
                    'twitter_username' => $account->twitter_username,
                    'twitter_name'=>$account->twitter_name,
                    'twitter_avatar'=>$account->twitter_avatar,
                    'total_points' => $account->total_points,
                    'rank' => $userRank,
                    'current_user' => true
                ];

                $topAccounts->prepend((object)$accountData);

                // Add the authorized user at their correct rank position as well
                $allAccounts = $topAccounts->toArray();
                array_splice($allAccounts, $userRank - 1, 0, [(object)$accountData]);
                $topAccounts = collect($allAccounts);
            }
        }

        return response()->json([
            'topAccounts' => $topAccounts,
        ]);
    }


}
