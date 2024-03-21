<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function claim(Request $request)
    {
        $account = AuthHelper::auth($request);

        if (!$account) {
            return response()->json(['message' => 'Non authorized'], 401);
        }

        $currentWeekNumber = Carbon::now()->format('W-Y');
//        $previousWeekNumber =
        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');


        $previousUserWeek = $account->weeks()
            ->where('week_number', $previousWeekNumber)
            ->where('active', false)
            ->first();

        if ($previousUserWeek) {
            $previousUserWeekPoints = $previousUserWeek->claim_points ?? 0;

            if ($previousUserWeekPoints) {

                $account->total_points += $previousUserWeekPoints;

//                $currentUserWeek->claim_points = 0;
                $previousUserWeek->claimed = true;
                $previousUserWeek->claimed_points = $previousUserWeekPoints;
                $previousUserWeek->claim_points = 0;

                $previousUserWeek->save();

                $account->isNeedShow= true;

                $account->save();

                return response()->json([], 200);
            }
        }
    }
}
