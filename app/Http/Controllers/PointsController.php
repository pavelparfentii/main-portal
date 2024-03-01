<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointsController extends Controller
{

    public function getPointsData(Request $request): JsonResponse
    {
        $account = AuthHelper::auth($request);
        $topAccounts = Account::with('discordRoles')
            ->select('id', 'wallet', 'twitter_username', 'total_points', 'twitter_name', 'twitter_avatar')
            ->orderByDesc('total_points')
            ->take(100)
            ->get();

        // Enrich the collection with rank and current_user flags
        $topAccounts->transform(function ($item, $key) use ($account) {
            $item->rank = $key + 1;
            $item->current_user = $account && $account->id == $item->id;
            return $item;
        });

        if ($account) {
            $userRank = DB::table('accounts')
                    ->where('total_points', '>', $account->total_points)
                    ->count() + 1;

            // Clone the account object to add as the current user at the top
            if ($userRank > 1) { // Assuming the current user is outside the top 100
                $currentUserForTop = clone $account;
                $currentUserForTop->rank = $userRank;
                $currentUserForTop->current_user = true;
                $currentUserForTop->load('discordRoles');

                // Insert the user at the top
                $topAccounts->prepend($currentUserForTop);

                // Insert the user at their actual rank if it's within the visible range
                if ($userRank <= 100) {
                    $currentUserForRank = clone $account;
                    $currentUserForRank->rank = $userRank;
                    $currentUserForRank->current_user = true;

                    $topAccounts->splice($userRank - 1, 0, [$currentUserForRank]);
                }
            }
        }

        // Use AccountResource to handle collection transformation
        return response()->json([
            'topAccounts' => AccountResource::collection($topAccounts),
        ]);
    }

    public function getInfo(Request $request): JsonResponse
    {
        $account = AuthHelper::auth($request);

        if ($account) {
            $userRank = DB::table('accounts')
                    ->where('total_points', '>', $account->total_points)
                    ->count() + 1;

            $account->setAttribute('rank', $userRank);
            $account->setAttribute('current_user', true);
            $account->load('discordRoles');
//            dd($account);
            return response()->json([
                'user' => new AccountResource($account),
                'global'=>[
                    'dropInfo'=>[
                        'nextDrop'=>Carbon::now()->endOfWeek(),
                        'lastDrop'=>Carbon::now()->startOfWeek()

                    ],
                    'total_users'=> DB::table('accounts')->count(),
                    'total_teams'=>'-'

                ]
            ]);

        }else{
            return response()->json([
                'user' => null,
                'global'=>[
                    'dropInfo'=>[
                        'nextDrop'=>Carbon::now()->endOfWeek(),
                        'lastDrop'=>Carbon::now()->startOfWeek()

                    ],
                    'total_users'=> DB::table('accounts')->count(),
                    'total_teams'=>'-'

                ]
            ]);
        }


    }



}
