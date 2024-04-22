<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;

class UpdateTwitterFollowersFollowings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-twitter-followers-followings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = new \GuzzleHttp\Client();
//        $accounts = Account::whereIn('twitter_id', ['820166162', '1360277900705099777'])->get();
        $accounts = Account::whereNotNull('twitter_id')->cursor();

//        $twitterId = $account->twitter_id;
        $startTime = microtime(true);
        $i =1;

        foreach ($accounts as $account){
            $this->info($i++);
            // Initialize arrays to hold rest IDs from followers and followings

            $followerRestIds = $this->getTwitterFollowers($client, 'followers', $account->twitter_id);
//            $followingRestIds = $this->getTwitterUserRestIds($client, $account->twitter_id, 'followings');
            $followingRestIds = $this->getTwitterFollowings($client,'followings', $account->twitter_id);

            var_dump($account->twitter_id);


            $mutualRestIds = array_intersect($followerRestIds, $followingRestIds);


            // Find mutual friend accounts based on Twitter IDs
            $mutualFriends = Account::whereIn('twitter_id', $mutualRestIds)->get();

            // Use syncWithoutDetaching to update mutual friend relationships
            foreach ($mutualFriends as $friend) {
                if ($account->id !== $friend->id) { // Prevent self-relationship
                    $account->friends()->syncWithoutDetaching([$friend->id]);
                    // If you need to ensure the inverse relationship is also set:
                    $friend->friends()->syncWithoutDetaching([$account->id]);
                }
            }
            usleep(20000 * $i);
        }


        $this->info('Update completed successfully.');

        // End timer
        $endTime = microtime(true);

        // Calculate and display the duration
        $duration = $endTime - $startTime;
        $this->info("Execution time: {$duration} seconds.");
    }

    private function getTwitterUserRestIds($client, $twitterId, $type)
    {
        $restIds = [];
        $bottomCursor = null;

        do {
            $url = "https://twitter241.p.rapidapi.com/{$type}?user=$twitterId&count=20";
            if (!empty($bottomCursor)) {
                $url .= "&cursor=$bottomCursor";
            }

            try {

                $response = $client->request('GET', $url, [
                    'headers' => [
                        'X-RapidAPI-Host' => 'twitter241.p.rapidapi.com',
                        'X-RapidAPI-Key' => '85c0d2d5d9msh8188cd5292dcd82p1e4a24jsn97b344b037a2'
                    ],
                ]);

                $responseBody = json_decode($response->getBody(), true);
                if(isset($responseBody['result'])){
                    foreach ($responseBody['result']['timeline']['instructions'] as $instruction) {
                        if (isset($instruction['entries'])) {
                            foreach ($instruction['entries'] as $entry) {
                                if (isset($entry['content']['itemContent']['user_results']['result']['rest_id'])) {
                                    $restIds[] = $entry['content']['itemContent']['user_results']['result']['rest_id'];
                                }
                            }
                        }
                    }
                }else{
                    break;
                }


                $bottomCursor = $responseBody['cursor']['bottom'];

                // Extract the first part of the bottom cursor to check if it's "0"
                list($firstPart, ) = explode('|', $bottomCursor, 2);
//                list($firstPart, ) = explode('|', $bottomCursor, 2);
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                // Log the error or handle it as needed
                $this->error("An error occurred while fetching data: " . $e->getMessage());
                // Optionally, break or continue depending on how you want to handle failures
                break;
            }

            // Exit the loop if there's no cursor or it's the end of the list
        } while (!empty($bottomCursor) && $firstPart !== "0");


        return $restIds;
    }

//    private function getTwitterFollowers($client, $twitterId, $type)
//    {
//        $restIds = [];
//        $next_cursor = null;
//
//        do {
//            try {
//
//                $url = "https://twitter241.p.rapidapi.com/{$type}.php?screenname=$twitterId";
//                if ($next_cursor !== null) {
//                    sleep(1);
//                    $url .= "&cursor=$next_cursor";
//                }
//
//                $response = $client->request('GET', $url, [
//                    'headers' => [
//                        'X-RapidAPI-Host' => 'twitter-api45.p.rapidapi.com',
//                        'X-RapidAPI-Key' => '85c0d2d5d9msh8188cd5292dcd82p1e4a24jsn97b344b037a2'
//                    ],
//                ]);
//
//                $responseBody = json_decode($response->getBody(), true);
//                if(isset($responseBody['followers'])){
//                    foreach ($responseBody['followers'] as $follower) {
//                        if(isset($follower['user_id'])){
//                            $restIds[] = $follower['user_id'];
//                        }
//                    }
//                }
//                $next_cursor = $responseBody['next_cursor'] ?? null;
//                $more_users = $responseBody['more_users'] ?? false;
//
//            }catch (\GuzzleHttp\Exception\GuzzleException $e){
//                $this->error("An error occurred while fetching data: " . $e->getMessage());
//                // Optionally, break or continue depending on how you want to handle failures
//                break;
//            }
//
//        }while ($next_cursor && $more_users);
//
//        return $restIds;
//    }

//    private function getTwitterFollowings($client, $twitterId, $type)
//    {
//        $restIds = [];
//        $next_cursor = null;
//
//        do {
//            try {
//                $url = "https://twitter241.p.rapidapi.com/{$type}.php?screenname=$twitterId";
//                if ($next_cursor !== null) {
//                    $url .= "&cursor=$next_cursor";
//                }
//
//                $response = $client->request('GET', $url, [
//                    'headers' => [
//                        'X-RapidAPI-Host' => 'twitter-api45.p.rapidapi.com',
//                        'X-RapidAPI-Key' => '85c0d2d5d9msh8188cd5292dcd82p1e4a24jsn97b344b037a2'
//                    ],
//                ]);
//
//                $responseBody = json_decode($response->getBody(), true);
//                if(isset($responseBody['following'])){
//                    foreach ($responseBody['following'] as $following) {
//                        if(isset($following['user_id'])){
//                            $restIds[] = $following['user_id'];
//                        }
//                    }
//                }
//                $next_cursor = $responseBody['next_cursor'] ?? null;
//                $more_users = $responseBody['more_users'] ?? false;
//
//            }catch (\GuzzleHttp\Exception\GuzzleException $e){
//                $this->error("An error occurred while fetching data: " . $e->getMessage());
//                // Optionally, break or continue depending on how you want to handle failures
//                break;
//            }
//
//        }while ($next_cursor && $more_users);
//
//        return $restIds;
//    }

    private function getTwitterFollowings($client, $type, $twitterId)
    {
        $restIds = [];
        $next_cursor = null;

        do {
            try {
                sleep(1);
                $url = "https://twitter288.p.rapidapi.com/user/{$type}/ids?id=$twitterId&count=5000";
                if ($next_cursor !== null) {
                    sleep(1);
                    $url .= "&cursor=$next_cursor";
                }


                $response = $client->request('GET', $url, [
                    'headers' => [
                        'X-RapidAPI-Host' => 'twitter288.p.rapidapi.com',
                        'X-RapidAPI-Key' => '85c0d2d5d9msh8188cd5292dcd82p1e4a24jsn97b344b037a2'
                    ],
                ]);

                $responseBody = json_decode($response->getBody(), true);
//                if($responseBody['ids']){
//                    foreach ($responseBody['ids'] as $follower) {
//                        $restIds[] = $follower;
//                    }
//
//                }
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

    private function getTwitterFollowers($client, $type, $twitterId)
    {
        $restIds = [];
        $next_cursor = null;

        do {
            try {

                $url = "https://twitter288.p.rapidapi.com/user/{$type}/ids?id=$twitterId&count=5000";
//                sleep(1);
                if ($next_cursor !== null) {
//                    sleep(1);
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


//                if($responseBody['ids']){
//
//
//                }
                $next_cursor = isset($responseBody['next_cursor_str']) && ($responseBody['next_cursor_str'] !== '0') ? $responseBody['next_cursor'] : null;


            }catch (\GuzzleHttp\Exception\GuzzleException $e){
                $this->error("An error occurred while fetching data: " . $e->getMessage());
                // Optionally, break or continue depending on how you want to handle failures
                break;
            }

        }while ($next_cursor);

        return $restIds;
    }

}
