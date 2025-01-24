<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Http\Resources\FriendResource;
use App\Http\Resources\TeamResource;
use App\Models\Account;
use Carbon\Carbon;
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
//            usleep(700000);
            $response = $client->request('GET', $url, [
                'headers' => [
                    'X-RapidAPI-Host' => 'twitter241.p.rapidapi.com',
//                    'X-RapidAPI-Key' => 'be57165779msh42e81c435e412e1p1a5e97jsn890a5a58f40f',
                    'X-RapidAPI-Key'=>'85c0d2d5d9msh8188cd5292dcd82p1e4a24jsn97b344b037a2'
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

            list($firstPart, ) = explode('|', $bottomCursor, 2);


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
            return response()->json(['message' => 'Non authorized'], 401);
        }

        $period = $request->input('period');

        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
        $loadRelations = ['discordRoles', 'team.accounts'];

        // Calculate total_points and week_points for the current user
        $currentUserWeekPoints = $account->weeks()
                ->where('week_number', '=', $previousWeekNumber)
                ->where('active', false)
                ->first()
                ->total_points ?? 0;


        if ($period == 'total') {

            $account->week_points = $currentUserWeekPoints;
        } else {

            $account->week_points = $currentUserWeekPoints;
        }

        $account->load($loadRelations);

        // Clone the current user data for inclusion in the response
        $currentUserData = clone $account;
        $currentUserData->current_user = true;


        $allAccounts = collect([$currentUserData]);

        // Get friends and calculate their points and ranks
        $friends = $account->friends()->with($loadRelations)->get()->each(function ($friend) use ($previousWeekNumber, $period) {
            $friendWeekPoints = $friend->weeks()
                    ->where('week_number', '=', $previousWeekNumber)
                    ->where('active', false)
                    ->first()
                    ->total_points ?? 0;

            if ($period == 'total') {
                $friendRank = DB::table('accounts')
                        ->where('total_points', '>', $friend->total_points)
                        ->count() + 1;
                $friend->week_points = $friendWeekPoints;
            }else {
                $friendRank = DB::table('accounts')
                        ->join('weeks', 'accounts.id', '=', 'weeks.account_id')
                        ->where('weeks.week_number', '=', $previousWeekNumber)
                        ->where('weeks.total_points', '>', $friendWeekPoints)
                        ->where('weeks.active', false)
                        ->count()+1;
                $friend->week_points = $friendWeekPoints;
            }
            $friend->rank = $friendRank;
            $friend->current_user = false;
        });

        $allAccounts = $allAccounts->merge($friends);


        if ($period == 'total') {
            $allAccounts = $allAccounts->sortByDesc('total_points')->values();
        } else {
            $allAccounts = $allAccounts->sortByDesc('week_points')->values();
        }

        return response()->json([
            'list' => FriendResource::collection($allAccounts),
        ]);
    }
}
