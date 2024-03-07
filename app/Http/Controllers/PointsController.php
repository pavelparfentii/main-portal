<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class PointsController extends Controller
{

    public function getPointsData(Request $request): JsonResponse
    {
//        $account = AuthHelper::auth($request);
//        $topAccounts = Account::with('discordRoles')
//            ->select('id', 'wallet', 'twitter_username', 'total_points', 'twitter_name', 'twitter_avatar')
//            ->orderByDesc('total_points')
//            ->take(100)
//            ->get();
//
//        // Enrich the collection with rank and current_user flags
//        $topAccounts->transform(function ($item, $key) use ($account) {
//            $item->rank = $key + 1;
//            $item->current_user = $account && $account->id == $item->id;
//            return $item;
//        });
//
//        if ($account) {
//            $userRank = DB::table('accounts')
//                    ->where('total_points', '>', $account->total_points)
//                    ->count() + 1;
//
//            // Clone the account object to add as the current user at the top
//            if ($userRank > 1) { // Assuming the current user is outside the top 100
//                $currentUserForTop = clone $account;
//                $currentUserForTop->rank = $userRank;
//                $currentUserForTop->current_user = true;
//                $currentUserForTop->load('discordRoles');
//                $currentUserForTop->load('followers');
//
//                $allFriends = $currentUserForTop->friends->merge($currentUserForTop->followers);
//                // Apply any transformation or additional logic to $allFriends here
//
//                // Then attach the transformed collection to the $currentUserForTop
//                $currentUserForTop->setAttribute('friend', $allFriends);
//
//                // Insert the user at the top
//                $topAccounts->prepend($currentUserForTop);
//
//            }
//        }
//
//        // Use AccountResource to handle collection transformation
//        return response()->json([
//            'topAccounts' => AccountResource::collection($topAccounts),
//        ]);

        $account = AuthHelper::auth($request);

        // Assuming 'friends' returns a collection of Account models representing the user's friends
        // Retrieve the IDs of all friends for the current user to optimize the check later

        $period = $request->input('period');

        if($period === 'total'){
            $friendIds = $account ? $account->friends->pluck('id')->toArray() : [];
//            $friendIds = $account->friends->pluck('account_friend.friend_id')->toArray();

            $topAccounts = Account::with('discordRoles')
                ->select('id', 'wallet', 'twitter_username', 'total_points', 'twitter_name', 'twitter_avatar')
                ->orderByDesc('total_points')
                ->take(100)
                ->get();

            // Enrich the collection with rank, current_user flags, and is_friend flag
            $topAccounts->transform(function ($item, $key) use ($account, $friendIds) {
                $item->rank = $key + 1;
                $item->current_user = $account && $account->id == $item->id;

                $item->friend = in_array($item->id, $friendIds);
                return $item;
            });

            if ($account) {
                $userRank = DB::table('accounts')
                        ->where('total_points', '>', $account->total_points)
                        ->count() + 1;

                if ($userRank > 1) { // Assuming the current user is outside the top 100
                    $currentUserForTop = clone $account;
                    $currentUserForTop->rank = $userRank;
                    $currentUserForTop->current_user = true;
                    $currentUserForTop->friend = false; // The user is not a friend to themselves
                    $currentUserForTop->load(['discordRoles', 'followers']);

                    $topAccounts->prepend($currentUserForTop);
                }
            }

            return response()->json([
                'list' => AccountResource::collection($topAccounts),
            ]);
        }else{
            return response()->json([
                'list' => [],
            ]);
        }

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
//            $account->setAttribute('invited', '-');
            $account->load('discordRoles');

            $accountResourceArray = (new AccountResource($account))->resolve();

            // Add isNeedShow directly
            $accountResourceArray['isNeedShow'] = false;
            $accountResourceArray['invited'] = "-";
//            dd($account);
            return response()->json([
                'user' => $accountResourceArray,
                'global'=>[
                    'dropInfo'=>[
                        'nextDrop'=>Carbon::now()->endOfWeek(),
                        'lastDrop'=>Carbon::now()->subWeek()->endOfWeek()

                    ],
                    'total_users'=> DB::table('accounts')->count(),
                    'total_teams'=>'-',
                    'friends'=>$account->friends->count() ?? [],

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
                    'total_teams'=>'-',
                    'friends'=>null
                ]
            ]);
        }
    }



}
