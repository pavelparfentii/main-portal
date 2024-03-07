<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
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

        if ($account->team_id) {

            $account->team_id = null;
            $account->save();
        }

        $slug = $request->slug;

        $team = Team::where('slug', $slug)->first();

        $account->team()->associate($team->id);

        $account->save();

        $team = $account->team()->first();
        $team->load('creator');

        return response()->json($team);

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

        // Calculate ranks and friend status for each account in the team
        foreach ($team->accounts as $teamAccount) {
            $teamAccount->friend = in_array($teamAccount->id, $friendIds);
            // This might not be efficient for large numbers of accounts; consider optimizing
            $teamAccount->rank = Account::where('total_points', '>', $teamAccount->total_points)->count() + 1;
            $teamAccount->current_user = ($currentUser->id === $teamAccount->id);
        }

//        $teamWithAccountsAndFriendStatus = Team::with(['accounts', 'accounts.friends' => function ($query) use ($accountId) {
//            $query->where('account_friend.friend_id', $accountId);
//        }])->first();




        return response()->json([
           'team'=>$team,
            'total_members'=>$team->accounts()->count(),
            'total_points'=>$team->accounts()->sum('total_points')
        ]);
    }
}
