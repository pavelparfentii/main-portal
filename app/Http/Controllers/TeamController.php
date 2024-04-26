<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Team;
use Carbon\Carbon;
use Dotenv\Util\Str;
use Illuminate\Http\Request;
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
//        // Calculate total points based on period
//        foreach ($team->accounts as $account) {
//            if ($period === 'week') {
//                $account->load(['weeks' => function ($query) use ($previousWeekNumber) {
//                    $query->where('week_number', $previousWeekNumber)->where('active', false);
//                }]);
//                $account->total_points = $account->weeks->sum('total_points');
//
//            } // If 'total', total_points is already loaded
//        }
//
//        // Sort accounts based on total_points in descending order
//        $sortedAccounts = $team->accounts->sortByDesc('total_points')->values();
//
//
//        if (!$currentUser) {
//            return response()->json([
//                'team' => $team->accounts->sortByDesc('total_points')->values(),
//                'total_members' => $sortedAccounts->count(),
//                'total_points' => $sortedAccounts->sum('total_points')
//            ], 200);
//        }
//
//        if ($token = $request->bearerToken() && !$currentUser) {
//            return response()->json(['error' => 'token expired or wrong'], 403);
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
//            'is_friend_of_creator' => $isFriendOfCreator,
//            'in_team' => $currentUser && $currentUser->team_id === $team->id
//        ]);
//    }

//normal
    public function getTeamList(Request $request)
    {
        $slug = $request->slug;

        $team = Team::where('slug', $slug)->with('creator')->with('accounts')->first();

        $period = $request->input('period', 'total');
//        $currentWeekNumber = Carbon::now()->format('W-Y');
        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');


        if(!$team){
            return response()->json(['message'=>'not found'], 404);
        }
        $currentUser = AuthHelper::auth($request);

        foreach ($team->accounts as $account) {
            // Retrieve the points for the current week, or default to 0 if none exist
            $weekPoints = DB::table('weeks')
                ->where('account_id', $account->id)
                ->where('week_number', $previousWeekNumber)
                ->where('active', false)
                ->sum('total_points'); // Using sum() directly on the query builder

            $account->week_points = $weekPoints; // Add week_points attribute to each account
        }

        if ($team) {
            $totalWeekPoints = 0;
            foreach ($team->accounts as $account) {
                // Sum only points for the current week
                $accountWeekPoints = $account->weeks
                    ->where('week_number', $previousWeekNumber)
                    ->where('active', false)
                    ->sum('total_points');
                $totalWeekPoints += $accountWeekPoints;
            }
        } else {
            // Keep the totalPoints calculation for 'total' period
            $totalWeekPoints = null; // Null or keep as totalPoints based on your preference
        }

        // If there's no authenticated user, return basic team info
        if (!$currentUser) {
            return response()->json([
                'team' => $team,
                'total_members' => $team->accounts->count(),
                'total_points' => $team->accounts->sum('total_points'),
                'week_points' => $totalWeekPoints
            ], 200);
        }

        $token = $request->bearerToken();

        if($token && !$currentUser){
            return response()->json(['error' => 'token expired or wrong'], 403);
        }

        $friendIds = $currentUser->friends()->pluck('id')->toArray();


        $isFriendOfCreator = $currentUser->id !== $team->creator->id && in_array($team->creator->id, $friendIds);

        if(empty($currentUser->twitter_username)){
            $isFriendOfCreator = false;
        }

//        if ($currentUser->id === $team->creator->id) {
//
//            $isFriendWithCreator = true;
//            $creatorIsFriendWithAccount = true;
//        }

        // Calculate ranks and friend status for each account in the team
        foreach ($team->accounts as $teamAccount) {

            unset($teamAccount->wallet);
            $teamAccount->rank = Account::where('total_points', '>', $teamAccount->total_points)->count() + 1;
            $teamAccount->current_user = ($currentUser->id === $teamAccount->id);
            if(empty($teamAccount->twitter_username) || empty($currentUser->twitter_username)){
                $teamAccount->friend = false;
                $teamAccount->is_friend_of_creator = false;
            }else{
                $teamAccount->friend = in_array($teamAccount->id, $friendIds);
                $teamAccount->is_friend_of_creator = $isFriendOfCreator;
            }
            $weekPoints = $teamAccount->weeks()
                ->where('week_number', $previousWeekNumber)
                ->where('active', false)
                ->sum('total_points');
            $teamAccount->week_points = $weekPoints;

            if(!empty($currentUser->discord_id)){
                $teamAccount->load('discordRoles');
            }

        }

        $sortedAccounts = $team->accounts->sortBy('rank');

// Optionally, reset the keys if you want them to be in sequential order after sorting
        $sortedAccounts = $sortedAccounts->values();

        $teamTotalWeekPoints = $team->accounts->sum(function($account) {
            return $account->week_points;
        });

// Replace the team's accounts with the sorted list
        $team->setRelation('accounts', $sortedAccounts);

        return response()->json([
            'team'=>$team,
            'total_members'=>$team->accounts()->count(),
            'total_points'=>$team->accounts()->sum('total_points'),
            'team_week_points' => $teamTotalWeekPoints,
//          Uncomment here
//            'is_friend_of_creator'=>$currentUser->id === $team->creator->id ? true : $isFriendOfCreator,
            'is_friend_of_creator'=>true,
            'in_team' => $currentUser && $currentUser->team_id === $team->id
        ]);
    }

//    public function getTeamList(Request $request)
//    {
//        $slug = $request->slug;
//        $team = Team::where('slug', $slug)->with(['creator', 'accounts'])->first();
//        $period = $request->input('period', 'total');
//        $currentWeekNumber = Carbon::now()->format('W-Y');
//        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
//
//        if (!$team) {
//            return response()->json(['message' => 'not found'], 404);
//        }
//
//        $currentUser = AuthHelper::auth($request);
//        if ($token = $request->bearerToken() && !$currentUser) {
//            return response()->json(['error' => 'token expired or wrong'], 403);
//        }
//
//        if ($team) {
//            $totalWeekPoints = 0;
//            foreach ($team->accounts as $account) {
//                // Sum only points for the current week
//                $accountWeekPoints = $account->weeks
//                    ->where('week_number', $previousWeekNumber)
//                    ->where('active', false)
//                    ->sum('total_points');
//                $totalWeekPoints += $accountWeekPoints;
//            }
//        } else {
//            // Keep the totalPoints calculation for 'total' period
//            $totalWeekPoints = null; // Null or keep as totalPoints based on your preference
//        }
//
//        if (!$currentUser) {
//            return response()->json([
//                'team' => $team,
//                'total_members' => $team->accounts->count(),
//                'total_points' => $team->accounts->sum('total_points'),
//                'week_points' => $totalWeekPoints
//            ], 200);
//        }
//
//        // Adjust point calculations based on the period
//        foreach ($team->accounts as $account) {
//            if ($period === 'week') {
//                $weekPoints = $account->weeks()
//                    ->where('week_number', $previousWeekNumber)
//                    ->where('active', false)
//                    ->sum('total_points');
//                $account->week_points = $weekPoints;
//            } else {
//                // Calculate total points from all weeks
//                $account->total_points = $account->weeks
//                    ->sum('total_points');
//            }
//        }
//
//        // Sorting by week_points if period is 'week', else sort by total_points
//        $sortedAccounts = $period === 'week'
//            ? $team->accounts->sortByDesc('week_points')
//            : $team->accounts->sortByDesc('total_points');
//
//        $sortedAccounts = $sortedAccounts->values();
//        $team->setRelation('accounts', $sortedAccounts);
//
//        $totalPoints = $team->accounts->sum($period === 'week' ? 'week_points' : 'total_points');
//
//        // Prepare response data
//        return response()->json([
//            'team' => $team,
//            'total_members' => $team->accounts->count(),
//            'total_points' => $team->accounts->sum('total_points'),
//            'week_points' => $totalPoints,
//            'is_friend_of_creator' => $currentUser && in_array($team->creator->id, $currentUser->friends()->pluck('id')->toArray()),
//            'in_team' => $currentUser && $currentUser->team_id === $team->id
//        ]);
//    }

    public function getTeamsList(Request $request)
    {

        $currentUser = AuthHelper::auth($request);

        $period = $request->input('period');

        if ($period === 'total') {

            $token = $request->bearerToken();

            if($token && !$currentUser){
                return response()->json(['error' => 'token expired or wrong'], 403);
            }

            $teams = Team::with(['creator', 'accounts'])->get();

            // Calculate total points for each team
            foreach ($teams as $team) {
                $team->team_total_points = $team->accounts->sum('total_points');
                foreach ($team->accounts as $account) {
                    unset($account->wallet);
                }
            }


            $teams = $teams->sortByDesc('team_total_points')->values();

            foreach ($teams as $index => $team) {

                $team->rank = $index + 1;
                unset($team->creator->wallet);

            }

            if($currentUser){
                $friendIds = $currentUser->friends()->pluck('id')->toArray();

                // Calculate total points for each team
                foreach ($teams as $team) {
                    $team->team_total_points = $team->accounts->sum('total_points');
                    foreach ($team->accounts as $account) {


//                        $account->friend = in_array($account->id, $friendIds);

                        if(empty($account->twitter_username)){
                            $account->friend = false;
                        }else{
                            $account->friend = in_array($account->id, $friendIds);
                        }
                    }
                }

                $isFriendOfCreator = $currentUser->id !== $team->creator->id && in_array($team->creator->id, $friendIds);

//                dd($currentUser);
                if(empty($currentUser->twitter_username)){

                    $isFriendOfCreator = false;
                }

                foreach ($teams as $index => $team) {
                    // Simply use the sorted position as a proxy for rank
                    $team->in_team = $currentUser && $currentUser->team_id === $team->id;

                    $team->is_friend = $isFriendOfCreator;


                }

                return response()->json(['list' => $teams]);
            }

            return response()->json(['list' => $teams]);
        }elseif ($period === 'week'){

            $token = $request->bearerToken();

            if($token && !$currentUser){
                return response()->json(['error' => 'token expired or wrong'], 403);
            }
//            $currentWeekNumber = Carbon::now()->format('W-Y'); // Current week number in format 'W-Y'
            $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

            $teams = Team::with(['creator', 'accounts.weeks' => function ($query) use ($previousWeekNumber) {
                // Filter the weeks related to the current week
                $query->where('week_number', '=', $previousWeekNumber)->where('active', false);
            }])->get();

// Calculate total points for each team for the current week
//            dd($currentUser);
            foreach ($teams as $team) {
                $teamTotalPoints = 0;
                foreach ($team->accounts as $account) {
                    // Summing up points from the current week
                    $accountWeekPoints = $account->weeks->sum('total_points');
                    $teamTotalPoints += $accountWeekPoints;

                    unset($account->wallet); // Unset wallet as before

                    // Determine if the account is a friend (assuming $currentUser is defined correctly)
                    if ($currentUser) {
                        $friendIds = $currentUser->friends()->pluck('id')->toArray();
                        $account->friend = !empty($account->twitter_username) && in_array($account->id, $friendIds);
                    }
                }
                $team->team_total_points = $teamTotalPoints; // Assign the calculated total points
            }

// Sort teams by their total points in descending order
            $teams = $teams->sortByDesc('team_total_points')->values();

            foreach ($teams as $index => $team) {

                $team->rank = $index + 1;
                unset($team->creator->wallet);

            }

//            return response()->json(['list' => $teams]);

            foreach ($teams as $index => $team) {
                $team->rank = $index + 1;
                unset($team->creator->wallet); // Unset creator's wallet as before

                // Assuming $currentUser is defined and checking if current user is in the team
                $team->in_team = $currentUser && $currentUser->team_id === $team->id;
            }

            return response()->json(['list' => $teams]);
        } else {
            return response()->json(['list' => []]);
        }

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
}
