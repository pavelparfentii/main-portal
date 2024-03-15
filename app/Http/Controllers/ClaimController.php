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


        $currentUserWeek = $account->weeks()
            ->where('week_number', $currentWeekNumber)
            ->where('active', true)
            ->first();

        if ($currentUserWeek) {
            $currentUserWeekPoints = $currentUserWeek->claim_points ?? 0;

            if ($currentUserWeekPoints > 0) {

                $account->total_points += $currentUserWeekPoints;


                $currentUserWeek->claim_points = 0;
                $currentUserWeek->claimed = true;
                $currentUserWeek->save();

                $account->save();

                return response()->json([], 200);
            }
        }
    }
}
