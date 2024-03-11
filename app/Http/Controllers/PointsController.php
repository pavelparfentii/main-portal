<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Http\Resources\TeamResource;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class PointsController extends Controller
{

    public function getPointsData(Request $request): JsonResponse
    {

        $account = AuthHelper::auth($request);


        $period = $request->input('period');

        if($period === 'total'){
            $friendIds = $account ? $account->friends->pluck('id')->toArray() : [];


            $loadRelations = ['discordRoles', 'friends', 'team.accounts'];
            // If the current user does not have a twitter_username, adjust the relations to be loaded

//            $friendIds = $account->friends->pluck('account_friend.friend_id')->toArray();

            $topAccounts = Account::with($loadRelations)
                ->select('id', 'wallet', 'twitter_username', 'total_points', 'twitter_name', 'twitter_avatar', 'team_id')
                ->orderByDesc('total_points')
                ->take(100)
                ->get();

            // Enrich the collection with rank, current_user flags, and is_friend flag
            $topAccounts->transform(function ($item, $key) use ($account, $friendIds) {
                $item->rank = $key + 1;
                $item->current_user = $account && $account->id == $item->id;
                $item->friend = in_array($item->id, $friendIds);
//                $item->load('team');
                $item->team = $item->team ? new TeamResource($item->team) : null;

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
                    if (empty($account->twitter_username)) {
                        $loadRelations = []; // Adjust this based on what you still want to load, if anything

                    }
                    $currentUserForTop->load($loadRelations);

                    $topAccounts->prepend($currentUserForTop);
                }
            }
            $token = $request->bearerToken();

            if($token && !$account){
                return response()->json(['error' => 'token expired or wrong'], 403);
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
//            $accountResourceArray['isNeedShow'] = false;
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
                    'total_teams'=>DB::table('teams')->count(),
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
                    'total_teams'=>DB::table('teams')->count(),
                    'friends'=>null
                ]
            ]);
        }
    }

    public function needShow(Request $request)
    {
        $account = AuthHelper::auth($request);

        if ($account) {
            $account->isNeedShow = !$account->isNeedShow;
            $account->save();
            return response()->json([
                'success' => true,
                'isNeedShow' => $account->isNeedShow
            ]);
        }else {
            // Handle the case where the account is not authenticated
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }



}
