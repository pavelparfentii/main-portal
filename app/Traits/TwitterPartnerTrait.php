<?php

namespace App\Traits;

use App\ConstantValues;
use App\Models\Account;
use App\Models\SafeSoul;
use App\Models\TwitterPartner;
use App\Models\Week;
use Illuminate\Database\Eloquent\Model;

trait TwitterPartnerTrait{

    public function updatePointsForTwitterFollowing()
    {
        $client = new \GuzzleHttp\Client();
//        $accounts = Account::whereIn('twitter_id', ['1496820396502433797', '545917486'])->get();
        $accounts = Account::whereNotNull('twitter_id')->cursor();

        $twitterPartners = TwitterPartner::query()->pluck('twitter_id')->toArray();
//        dd($twitterPartners);

        foreach ($accounts as $account){
            $followingRestIds = $this->getTwitterFollowings($client, 'followings', $account->twitter_id);
//            dd($followingRestIds);

            $intersection = array_intersect($followingRestIds, $twitterPartners);

            if(count($intersection)>0){
                foreach ($intersection as $key=>$item){
                    $partner = TwitterPartner::where('twitter_id', $item)->first();

                    $key = $this->convertToSnakeCase($partner->name);

                    $safeSoul =  SafeSoul::where('query_param', $key)
                        ->where('account_id', $account->id)
                        ->first();

                    if(!$safeSoul){
                        $currentWeek = Week::getCurrentWeekForAccount($account);


                        $safeSoul = new SafeSoul([
                            'account_id' => $account->id,
                            'claim_points' => $partner->points,
                            'comment' => $key,
                            'query_param' => $key
                        ]);
                        $currentWeek->safeSouls()->save($safeSoul);
                        $currentWeek->increment('claim_points', $partner->points);
                    }
                }
            }

        }
    }

    private function getTwitterFollowings($client, $type, $twitterId)
    {
        $restIds = [];
        $next_cursor = null;

        do {
            try {

                $url = "https://twitter288.p.rapidapi.com/user/{$type}/ids?id=$twitterId&count=5000";
                if ($next_cursor !== null) {
                    sleep(2);
                    $url .= "&cursor=$next_cursor";
                }

                $response = $client->request('GET', $url, [
                    'headers' => [
                        'X-RapidAPI-Host' => 'twitter288.p.rapidapi.com',
                        'X-RapidAPI-Key' => '85c0d2d5d9msh8188cd5292dcd82p1e4a24jsn97b344b037a2'
                    ],
                ]);

                $responseBody = json_decode($response->getBody(), true);

                if (isset($responseBody['error'])) {

                    $this->error("Error from API: " . $responseBody['error']);
                    // Check for rate limiting or other headers
                    $retryAfter = $response->getHeader('Retry-After');
                    if ($retryAfter) {
                        $this->info("Retry after: " . $retryAfter[0]);
                        sleep($retryAfter[0]);
                    } else {
                        break; // Exit the loop if there is an error without a retry header
                    }
                }

                if(array_key_exists('ids', $responseBody)){
                    foreach ($responseBody['ids'] as $follower) {
                        $restIds[] = $follower;
                    }
                }
                $next_cursor = isset($responseBody['next_cursor_str']) && ($responseBody['next_cursor_str'] !== '0') ? $responseBody['next_cursor'] : null;



            }catch (\GuzzleHttp\Exception\GuzzleException $e){
                $this->error("An error occurred while fetching data: " . $e->getMessage());
                // Optionally, break or continue depending on how you want to handle failures
                break;
            }

        }while ($next_cursor);

        return $restIds;
    }

    private function convertToSnakeCase($input) {
        // Convert spaces to underscores
        $input = str_replace(' ', '_', $input);
        // Convert to lowercase
        $input = 'follow_'. strtolower($input);
        return $input;
    }
}
