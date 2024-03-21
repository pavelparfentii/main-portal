<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Http\Resources\TeamResource;
use App\Models\Account;
use App\Models\Week;
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
                if(!empty($account->twitter_username)){
                    $item->friend = in_array($item->id, $friendIds);
                }else{
                    $item->friend = false;
                }

                $item->team = $item->team ? new TeamResource($item->team) : null;

                return $item;
            });

            $token = $request->bearerToken();

            if($token && !$account){
                return response()->json(['error' => 'token expired or wrong'], 403);
            }

            if ($account) {

                $userRank = DB::table('accounts')
                        ->where('total_points', '>', $account->total_points)
                        ->count() + 1;

                if ($userRank > 1) { // Assuming the current user is outside the top 100
                    $currentUserForTop = clone $account;
                    $currentUserForTop->rank = $userRank;
                    $currentUserForTop->current_user = true;
                    $currentUserForTop->friend = false; // The user is not a friend to themselves
                    // if (empty($account->twitter_username)) {
                    //     $loadRelations = []; // Adjust this based on what you still want to load, if anything

                    // }
                    $currentUserForTop->load($loadRelations);

                    $topAccounts->prepend($currentUserForTop);
                }
            }


            return response()->json([
                'list' => AccountResource::collection($topAccounts),
            ]);
        }elseif($period === 'week') {

            $friendIds = $account ? $account->friends->pluck('id')->toArray() : [];

            $loadRelations = ['discordRoles', 'friends', 'team.accounts'];
//            $currentWeekNumber = Carbon::now()->format('W-Y'); // Формат тиждень-рік, наприклад "03-2024"
            $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

            $topAccounts = Account::with($loadRelations)
                ->join('weeks', function ($join) use ($previousWeekNumber) {
                    $join->on('accounts.id', '=', 'weeks.account_id')
                        ->where('weeks.week_number', '=', $previousWeekNumber) // Тільки записи для поточного тижня
                        ->where('weeks.active', '=', false); // Враховуємо тільки активні записи, якщо потрібно
                })
                ->select('accounts.id', 'accounts.wallet', 'accounts.twitter_username', 'weeks.points as total_points', 'accounts.twitter_name', 'accounts.twitter_avatar', 'accounts.team_id')
                ->orderByDesc('weeks.points') // Сортування за очками поточного тижня
                ->take(100)
                ->get();

            $topAccounts->transform(function ($item, $key) use ($account, $friendIds) {
                $item->rank = $key + 1;
                $item->current_user = $account && $account->id == $item->id;
                if(!empty($account->twitter_username)){
                    $item->friend = in_array($item->id, $friendIds);
                }else{
                    $item->friend = false;
                }

                $item->team = $item->team ? new TeamResource($item->team) : null;

                return $item;
            });

            $token = $request->bearerToken();

            if($token && !$account){
                return response()->json(['error' => 'token expired or wrong'], 403);
            }

            if ($account) {
                // Визначаємо номер поточного тижня
//                $currentWeekNumber = Carbon::now()->format('W-Y');
                $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
                // Отримуємо очки користувача за поточний тиждень
                $currentUserWeekPoints = $account->weeks()
                        ->where('week_number', $previousWeekNumber)
                        ->where('active', false) // Враховуємо активні тижні, якщо потрібно
                        ->first()
                        ->points ?? 0;
//                dd($currentUserWeekPoints);
                // Розраховуємо ранг користувача на основі його очок за тиждень
                $userRankBasedOnWeekPoints = DB::table('accounts')
                        ->join('weeks', 'accounts.id', '=', 'weeks.account_id')
                        ->where('weeks.week_number', '=', $previousWeekNumber)
                        ->where('weeks.points', '>', $currentUserWeekPoints)
                        ->where('weeks.active', false)
                        ->count() + 1;

                // Якщо користувач не увійшов до топ-100
//                dd($userRankBasedOnWeekPoints);
                if ($userRankBasedOnWeekPoints > 1) {
                    $currentUserForTop = clone $account;
                    $currentUserForTop->total_points = $currentUserWeekPoints; // Використовуємо очки за тиждень
                    $currentUserForTop->rank = $userRankBasedOnWeekPoints;
                    $currentUserForTop->current_user = true;
                    $currentUserForTop->friend = false;

                    $currentUserForTop->load($loadRelations);

                    // Додаємо дані поточного користувача на початок колекції
                    $topAccounts->prepend($currentUserForTop);
                }
            }

            return response()->json([
                'list' => AccountResource::collection($topAccounts),
            ]);

        } else {
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

            if(!empty($account->discord_id)){
                $account->load('discordRoles');
            }

            $accountResourceArray = (new AccountResource($account))->resolve();


            $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

//            dd($account->id);
            $currentUserWeekPoints = $account->weeks()
                    ->where('week_number', $previousWeekNumber)
                    ->where('active', false)
                    ->where('claimed', false)
                    ->first()
                    ->claim_points ?? null;
//            dd($currentUserWeekPoints);

            // if($account->isNeedShow){
            $accountResourceArray['claimed_points']= $currentUserWeekPoints ?? $account->weeks()
                    ->where('week_number', $previousWeekNumber)
                    ->where('active', false)
                    ->where('claimed', true)
                    ->first()
                    ->claim_points ?? null;

            $accountResourceArray['invited'] = $account->invitesSent()->count() ?? 0;
            // }

            $claimed = $account->weeks()
                ->where('week_number', $previousWeekNumber)
                ->where('active', false)
                ->where('claimed', false)->first();

//            $currentWeekNumber = Carbon::now()->format('W-Y');

            // Отримуємо очки користувача за поточний тиждень


//            dd($currentUserWeekPoints);

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
                    'friends'=>!empty($account->twitter_username) ? $account->friends->count() : null,
                    'claimed'=>$claimed ? false : true,

                ]
            ]);

        }else{
            return response()->json([
                'user' => null,
                'global'=>[
                    'dropInfo'=>[
                        'nextDrop'=>Carbon::now()->endOfWeek(),
                        'lastDrop'=>Carbon::now()->subWeek()->endOfWeek()

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
