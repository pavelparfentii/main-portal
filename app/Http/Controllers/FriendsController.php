<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Http\Resources\FriendResource;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class FriendsController extends Controller
{
    public function getFriends()
    {
        $client = new \GuzzleHttp\Client();

        $account = Account::where('twitter_username', 'Sashacrave')->first();

        $twitterId = $account->twitter_id;

        $restIds = [];

        do {

            $url = "https://twitter241.p.rapidapi.com/followers?user=$twitterId&count=20";
            if (!empty($bottomCursor)) {
                $url .= "&cursor=$bottomCursor"; // Add the cursor to the URL if it's not the first request
            }
            usleep(700000);
            $response = $client->request('GET', $url, [
                'headers' => [
                    'X-RapidAPI-Host' => 'twitter241.p.rapidapi.com',
                    'X-RapidAPI-Key' => 'be57165779msh42e81c435e412e1p1a5e97jsn890a5a58f40f',
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);
            $responseArray = $responseBody;

            foreach ($responseArray['result']['timeline']['instructions'] as $instruction) {
                if (isset($instruction['entries'])) {
                    foreach ($instruction['entries'] as $entry) {
                        if (isset($entry['content']['itemContent']['user_results']['result']['rest_id'])) {
                            $restIds[] = $entry['content']['itemContent']['user_results']['result']['rest_id'];
                        }
                    }
                }
            }

            $bottomCursor = $responseBody['cursor']['bottom'];

            // Extract the first part of the bottom cursor to check if it's "0"
            list($firstPart, ) = explode('|', $bottomCursor, 2);

            // Process the response, extract rest_id, etc.

        } while ($firstPart !== "0");

        if(count($restIds)>0){
            foreach ($restIds as $twitter_id){
                $friend = Account::where('twitter_id', $twitter_id)->first();
                    if($friend){
                        $account->friends()->syncWithoutDetaching([$friend->id]);
                }

            }
        }

        $fgs = Account::with('friends')->where('twitter_username', 'Sashacrave')->first();

        return $fgs;
    }

    public function getFriendsForAccount(Request $request)
    {

        $account = AuthHelper::auth($request);

        if (!$account) {
            return response()->json(['message' => 'non authorized'], 401);
        }

        $friends = $account->friends()->with('discordRoles')->get();
        if(count($friends) > 0){
            $friends = $account->friends()->with('discordRoles')->get();
        }elseif (count($account->followers()->with('discordRoles')->get()) > 0){
            $friends = $account->followers()->with('discordRoles')->get();
        }else{
            $friends = $account->friends()->with('discordRoles')->get();
        }

        // Assign rank to each friend
        foreach ($friends as $friend) {
            $friendRank = DB::table('accounts')
                    ->where('total_points', '>', $friend->total_points)
                    ->count() + 1;
            $friend->rank = $friendRank;
        }

        // Calculate rank for the current user
        $userRank = DB::table('accounts')
                ->where('total_points', '>', $account->total_points)
                ->count() + 1;
        $account->rank = $userRank;


        // Unconditionally include the current user at the top
        $topCurrentUser = clone $account;
        $topCurrentUser->current_user = true;
        $topCurrentUser->load('discordRoles');
        $allAccounts = collect([$topCurrentUser]);

        // Apply rank filter to friends and possibly include current user in their rank position
        $rankThreshold = 100;
        $filteredAndRankedFriends = $friends->push($account) // Add current user for ranking
        ->sortBy('rank') // Ensure the collection is sorted by rank
        ->filter(function ($friend) use ($rankThreshold) {
            return $friend->rank <= $rankThreshold;
        });

        // Merge the top current user with the filtered and ranked list of friends
        $allAccounts = $allAccounts->merge($filteredAndRankedFriends);

        return response()->json([
            'friends' => FriendResource::collection($allAccounts),
        ]);

    }
}
