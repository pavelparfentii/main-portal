<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Team;
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

        $isFriendWithCreator = $account->friends()->where('id', $team->account_id)->exists();

        $creatorIsFriendWithAccount = $team->creator->friends()->where('id', $account->id)->exists();


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
            return response()->json(['message' => 'You must be friends with the team creator to join'], 403);
        }



    }

    public function getTeamList(Request $request)
    {
        $slug = $request->slug;

        $team = Team::where('slug', $slug)->with('creator')->with('accounts.discordRoles')->first();

        if(!$team){
            return response()->json(['message'=>'not found'], 403);
        }
        $currentUser = AuthHelper::auth($request);

        // If there's no authenticated user, return basic team info
        if (!$currentUser) {
            return response()->json([
                'team' => $team,
                'total_members' => $team->accounts->count(),
                'total_points' => $team->accounts->sum('total_points')
            ], 200);
        }

        // Get IDs of all friends of the currently authenticated user
        $friendIds = $currentUser->friends()->pluck('id')->toArray();


        $isFriendOfCreator = $currentUser->id !== $team->creator->id && in_array($team->creator->id, $friendIds);

        // Calculate ranks and friend status for each account in the team
        foreach ($team->accounts as $teamAccount) {
            $teamAccount->friend = in_array($teamAccount->id, $friendIds);

            // This might not be efficient for large numbers of accounts; consider optimizing
            $teamAccount->rank = Account::where('total_points', '>', $teamAccount->total_points)->count() + 1;
            $teamAccount->current_user = ($currentUser->id === $teamAccount->id);
            $teamAccount->is_friend_of_creator = $isFriendOfCreator;
        }

        $sortedAccounts = $team->accounts->sortBy('rank');

// Optionally, reset the keys if you want them to be in sequential order after sorting
        $sortedAccounts = $sortedAccounts->values();

// Replace the team's accounts with the sorted list
        $team->setRelation('accounts', $sortedAccounts);

        return response()->json([
            'team'=>$team,
            'total_members'=>$team->accounts()->count(),
            'total_points'=>$team->accounts()->sum('total_points')
        ]);
    }

    public function getTeamsList(Request $request)
    {

        $currentUser = AuthHelper::auth($request);

        $period = $request->input('period');

        if ($period === 'total') {
            // Load teams with their creators and accounts eagerly
            $teams = Team::with(['creator', 'accounts'])->get();

            // Calculate total points for each team
            foreach ($teams as $team) {
                $team->team_total_points = $team->accounts->sum('total_points');
            }

            // Sort teams by their total points in descending order
            $teams = $teams->sortByDesc('team_total_points')->values();

            // Unfortunately, calculating rank directly in a query similar to the accounts example is not straightforward
            // because we're working with a sum of points across related models. We'll use a simplified approach:
            foreach ($teams as $index => $team) {
                // Simply use the sorted position as a proxy for rank
                $team->rank = $index + 1;
            }

            if($currentUser){
                foreach ($teams as $index => $team) {
                    // Simply use the sorted position as a proxy for rank
                    $team->in_team = $currentUser && $currentUser->team_id === $team->id;
                }

                return response()->json(['list' => $teams]);
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
