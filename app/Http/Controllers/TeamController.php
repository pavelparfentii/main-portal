<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Team;
use Carbon\Carbon;
use Dotenv\Util\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    public function makeTeam(Request $request)
    {
        $account = AuthHelper::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:teams|max:255',
            'team_avatar' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $slug = \Illuminate\Support\Str::slug($request->name);
        $slugCount = Team::where('slug', $slug)->count();
        if ($slugCount > 0) {
            $slug = "{$slug}-{$slugCount}";
        }

        $path = $request->hasFile('team_avatar')
            ? $request->file('team_avatar')->store('team_avatars', 'public')
            : null;

        $fullUrl = $path ? url('storage/' . $path) : null;
        $teamData = [
            'name' => $request->name,
            'slug' => $slug,
            'team_avatar' =>$fullUrl,
            'account_id'=> $account->id
        ];

        $account->createTeamAndAssign($teamData);

        $team = $account->team()->first();
        $team->load('creator');

        return response()->json($team);


    }

    public function joinTeam(Request $request)
    {
        $account = AuthHelper::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        $slug = $request->slug;

        $team = Team::where('slug', $slug)->first();

        if ($account->team_id == $team->id) {
            return response()->json(['message' => 'You are already a member of this team'], 400);
        }

        if ($account->id === $team->creator->id) {

            $isFriendWithCreator = true;
            $creatorIsFriendWithAccount = true;
        } else {

            $isFriendWithCreator = $account->friends()->where('id', $team->account_id)->exists();

            $creatorIsFriendWithAccount = $team->creator->friends()->where('id', $account->id)->exists();
        }

        if ($isFriendWithCreator && $creatorIsFriendWithAccount) {

            if ($account->team_id) {

                $account->team_id = null;
                $account->save();
            }

            $account->team()->associate($team->id);

            $account->save();

            $team = $account->team()->first();
            $team->load('creator');
            $team->load('accounts.discordRoles');

            return response()->json($team);
        } else {
            // Return an error if the friendship condition is not met
            //UNCOMMENT WHEN READY
//            return response()->json(['message' => 'You must be friends with the team creator to join'], 403);
            if ($account->team_id) {

                $account->team_id = null;
                $account->save();
            }

            $account->team()->associate($team->id);

            $account->save();

            $team = $account->team()->first();
            $team->load('creator');
            $team->load('accounts.discordRoles');

            return response()->json($team);
        }

    }

//    public function getTeamList(Request $request)
//    {
//        $slug = $request->slug;
//        $team = Team::where('slug', $slug)->with(['creator', 'accounts'])->first();
//        $period = $request->input('period', 'total');
//        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
//
//        if (!$team) {
//            return response()->json(['message' => 'not found'], 404);
//        }
//
//        $currentUser = AuthHelper::auth($request);
//
//        foreach ($team->accounts as $account) {
//            if ($period === 'week') {
//                $account->load(['weeks' => function ($query) use ($previousWeekNumber) {
//                    $query->where('week_number', $previousWeekNumber)->where('active', false);
//                }]);
//
//                $account->total_points = $account->weeks->sum('total_points');
//            } else {
//
////                $account->display_points = $account->total_points;
//            }
//        }
//
//        // Sort accounts based on display_points in descending order
//        $sortedAccounts = $team->accounts->sortByDesc('total_points')->values();
//
//        if (!$currentUser) {
//            return response()->json([
//                'team' => $team,
//                'sorted_accounts' => $sortedAccounts,
//                'total_members' => $sortedAccounts->count(),
//                'total_points' => $sortedAccounts->sum('total_points')
//            ], 200);
//        }
//
//        $friendIds = $currentUser ? $currentUser->friends()->pluck('id')->toArray() : [];
//        $isFriendOfCreator = in_array($team->creator->id, $friendIds);
//
//        foreach ($sortedAccounts as $account) {
//            $account->is_friend_of_creator = $isFriendOfCreator;
//            $account->in_team = $currentUser && $currentUser->team_id === $team->id;
//        }
//
//        return response()->json([
//            'team' => $team,
//            'sorted_accounts' => $sortedAccounts,
//            'total_members' => $sortedAccounts->count(),
//            'total_points' => $sortedAccounts->sum('total_points'),
////            'is_friend_of_creator' => $isFriendOfCreator,
//            'is_friend_of_creator' => true,
//            'in_team' => $currentUser && $currentUser->team_id === $team->id
//        ]);
//    }
    public function getTeamList(Request $request)
    {
        $slug = $request->slug;
        $team = Team::where('slug', $slug)->with(['creator', 'accounts'])->first();
        $period = $request->input('period', 'total');
        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

        if (!$team) {
            return response()->json(['message' => 'not found'], 404);
        }

        $currentUser = AuthHelper::auth($request);

        foreach ($team->accounts as $account) {

            $account->load(['weeks' => function ($query) use ($previousWeekNumber) {
                $query->where('week_number', $previousWeekNumber)->where('active', false);
            }]);
            $account->week_points = $account->weeks->sum('total_points');

        }

        // Sort accounts based on the selected period points in descending order
        if ($period === 'week') {
            $sortedAccounts = $team->accounts->sortByDesc('week_points')->values();
        } else {
            $sortedAccounts = $team->accounts->sortByDesc('total_points')->values();
        }

        // Add rank to each account
        foreach ($sortedAccounts as $index => $account) {
            $account->rank = $index + 1;
        }

        if (!$currentUser) {
            return response()->json([
                'team' => $team,
                'sorted_accounts' => $sortedAccounts,
                'total_members' => $sortedAccounts->count(),
                'total_points' => $sortedAccounts->sum('total_points')
            ], 200);
        }

        $friendIds = $currentUser ? $currentUser->friends()->pluck('id')->toArray() : [];
        $isFriendOfCreator = in_array($team->creator->id, $friendIds);

        foreach ($sortedAccounts as $account) {
            $account->friend = !empty($account->twitter_username) && in_array($account->id, $friendIds);
            $account->in_team = $currentUser && $currentUser->team_id === $team->id;
        }

        return response()->json([
            'team' => $team,
            'sorted_accounts' => $sortedAccounts,
            'total_members' => $sortedAccounts->count(),
            'total_points' => $sortedAccounts->sum('total_points'),
            'is_friend_of_creator' => $isFriendOfCreator,
            'in_team' => $currentUser && $currentUser->team_id === $team->id
        ]);
    }


//    public function getTeamsList(Request $request)
//    {
//        $currentUser = AuthHelper::auth($request);
//        $period = $request->input('period');
//        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
//        $loadRelations = ['creator', 'accounts'];
//
//        // if ($token = $request->bearerToken() && !$currentUser) {
//        //     return response()->json(['error' => 'token expired or wrong'], 401);
//        // }
//
//        $teams = Team::with($loadRelations)->get();
//
//        foreach ($teams as $team) {
//            $teamTotalPoints = 0;
//            $teamWeekPoints = 0;
//            foreach ($team->accounts as $account) {
//                $accountWeekPoints = $account->weeks()
//                    ->where('week_number', $previousWeekNumber)
//                    ->where('active', false)
//                    ->sum('total_points');
//
//                $teamTotalPoints += $account->total_points;
//                $teamWeekPoints += $accountWeekPoints;
//
//                unset($account->wallet);
//
//                if ($currentUser) {
//                    $friendIds = $currentUser->friends()->pluck('id')->toArray();
//                    $account->friend = !empty($account->twitter_username) && in_array($account->id, $friendIds);
//                }
//            }
//            $team->team_total_points = $teamTotalPoints;
//            $team->team_week_points = $teamWeekPoints;
//        }
//
//        if ($period === 'total') {
//            $teams = $teams->sortByDesc('team_total_points')->values();
//        } else {
//            $teams = $teams->sortByDesc('team_week_points')->values();
//        }
//
//        foreach ($teams as $index => $team) {
//            $team->rank = $index + 1;
//            unset($team->creator->wallet);
//
//            if ($currentUser) {
//                $friendIds = $currentUser->friends()->pluck('id')->toArray();
//                $isFriendOfCreator = $currentUser->id !== $team->creator->id && in_array($team->creator->id, $friendIds);
//
//                if (empty($currentUser->twitter_username)) {
//                    $isFriendOfCreator = false;
//                }
//
//                $team->in_team = $currentUser->team_id === $team->id;
//                $team->is_friend = $isFriendOfCreator;
//            }
//        }
//
//        return response()->json(['list' => $teams]);
//    }

    public function getTeamsList(Request $request)
    {
        $currentUser = AuthHelper::auth($request);
        $period = $request->input('period');
        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
        $loadRelations = ['creator', 'accounts'];

        $teams = Team::with($loadRelations)->get();

        $cacheKey = $this->generateCacheKey($currentUser, $period);

        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        foreach ($teams as $team) {
            $teamTotalPoints = 0;
            $teamWeekPoints = 0;
            foreach ($team->accounts as $account) {
                $accountWeekPoints = $account->weeks()
                    ->where('week_number', $previousWeekNumber)
                    ->where('active', false)
                    ->sum('total_points');

                $teamTotalPoints += $account->total_points;
                $teamWeekPoints += $accountWeekPoints;

                unset($account->wallet);

                if ($currentUser) {
                    $friendIds = $currentUser->friends()->pluck('id')->toArray();
                    $account->friend = !empty($account->twitter_username) && in_array($account->id, $friendIds);
                }
            }
            $team->team_total_points = $teamTotalPoints;
            $team->team_week_points = $teamWeekPoints;
        }

        // Calculate ranks for the current period
        $teamsSortedByCurrentPoints = $teams->sortByDesc('team_total_points')->values();
        foreach ($teamsSortedByCurrentPoints as $index => $team) {
            $team->current_rank = $index + 1;
        }

        // Calculate deducted points and ranks for the previous period
        foreach ($teams as $team) {
            $team->deducted_points = $team->team_total_points - $team->team_week_points;
        }

        $teamsSortedByDeductedPoints = $teams->sortByDesc('deducted_points')->values();
        foreach ($teamsSortedByDeductedPoints as $index => $team) {
            $team->previous_rank = $index + 1;
        }

        // Save the ranks to the database
        // foreach ($teams as $team) {
        //     $team->save();
        // }

        if ($period === 'total') {
            $teams = $teamsSortedByCurrentPoints;
        } else {
            $teams = $teamsSortedByDeductedPoints;
        }

        foreach ($teams as $index => $team) {
            // $team->rank = $index + 1;
            unset($team->creator->wallet);
            unset($team->deducted_points);

            if ($currentUser) {
                $friendIds = $currentUser->friends()->pluck('id')->toArray();
                $isFriendOfCreator = $currentUser->id !== $team->creator->id && in_array($team->creator->id, $friendIds);

                if (empty($currentUser->twitter_username)) {
                    $isFriendOfCreator = false;
                }

                $team->in_team = $currentUser->team_id === $team->id;
                $team->friend = $isFriendOfCreator;
            }
        }

        $result = ['list' => $teams];
//        return response()->json(['list' => $teams]);

        Cache::put($cacheKey, $result, now()->addHours(36));

        return response()->json($result);
    }

    public function leaveTeam(Request $request)
    {

        $account = AuthHelper::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        if ($account->team_id) {

            $account->team_id = null;
            $account->save();

            return response()->json(['message' => 'success'], 200);
        }
    }

    public function checkName(Request $request)
    {
        $account = AuthHelper::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:teams|max:255|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $slug = \Illuminate\Support\Str::slug($request->name);


        $teamWithSlug = Team::where('slug', $slug)->first();

        if ($teamWithSlug) {

            return response()->json(['message' => 'The name has already been taken and cannot be used.'], 422) ;
        }

    }

    private function generateCacheKey($currentUser, $period): string
    {
        $userId = $currentUser ? $currentUser->id : 'guest';
        return "teams_list_{$period}_{$userId}";
    }
}
