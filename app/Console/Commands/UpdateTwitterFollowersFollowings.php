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
//        $account = Account::where('twitter_username', 'Sashacrave')->first();
        $accounts = Account::whereNotNull('twitter_id')->cursor();

//        $twitterId = $account->twitter_id;
        $startTime = microtime(true);
        $i =1;

        foreach ($accounts as $account){
            $this->info($i++);
            // Initialize arrays to hold rest IDs from followers and followings

            $followerRestIds = $this->getTwitterUserRestIds($client, $account->twitter_id, 'followers');
            $followingRestIds = $this->getTwitterUserRestIds($client, $account->twitter_id, 'followings');

            // Merge and remove duplicate rest IDs from both sets
            $uniqueRestIds = array_unique(array_merge($followerRestIds, $followingRestIds));
            foreach ($uniqueRestIds as $twitter_id) {
                $friend = Account::where('twitter_id', $twitter_id)->first();
                if($friend){
                    $account->friends()->syncWithoutDetaching([$friend->id]);
                }
            }
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

            // Extract the first part of the bottom cursor to check if it's "0"
//            list($firstPart, ) = explode('|', $bottomCursor, 2);

            // Exit the loop if there's no cursor or it's the end of the list
        } while (!empty($bottomCursor) && $firstPart !== "0");

        return $restIds;
    }
}
