<?php


namespace App\Traits;


use App\ConstantValues;
use App\Models\Account;
use App\Models\Twitter;
use App\Models\TwitterAction;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait TwitterTrait
{
    private string $apiKey = 'AAAAAAAAAAAAAAAAAAAAAPm6VwEAAAAAPtJazJxkDVcgvEGelXFaSoNGzms%3D1CLa79WikRBMb1ugbOJTFOhinkNjdgloNl4AsxgQfytCSaltSd';

    //Projects mention

    public function getProjectsPosts()
    {
        $processedAuthors = [];

        $results = self::getPosts($this->apiKey);

        $totalLikesPoints = 0;
        $totalImpressionsPoints = 0;
        $totalRetweetPoints = 0;

        $authorPosts = []; // Store the number of posts for each author
        $activeAuthors = []; // Store authors with daily posts throughout the week

        while(isset($results['data'])){
            foreach ($results['data'] as $result) {
//                dd($results['data']);

                $likesCount = $result['public_metrics']['like_count'] ?? 0;

                $impressionCount = $result['public_metrics']['impression_count'] ?? 0;

                $retweetCount = $result['public_metrics']['retweet_count'] ?? 0;

                $authorId = $result['author_id'];

                $tweetDate = Carbon::parse($result['created_at'])->format('Y-m-d');

                $account = Account::where('twitter_id', $result['author_id'])->first();

                if ($account && (!isset($processedAuthors[$authorId]) || $processedAuthors[$authorId] <= 7)) {

                    $currentWeek = Week::getCurrentWeekForAccount($account);

                    $queryParam = 'twitter_post_twitter_id' . $result['id'];

                    $existingTwitter = Twitter::where('query_param', $queryParam)
                        ->where('account_id', $account->id)
                        ->first();

                    if(!$existingTwitter){
                        $twitter = new Twitter([
                            'account_id'=> $account->id,
                            'claim_points' => ConstantValues::twitter_project_tweet,
                            'comment' => 'Пост в Twitter с отметкой проекта или проектов post_id=' . $result['id'],
                            'query_param' => $queryParam
                        ]);
                        $currentWeek->twitters()->save($twitter);
                        $currentWeek->increment('claim_points', ConstantValues::twitter_project_tweet);

                        $twitterAction = $account->getTwitterAction();
                        if ($twitterAction) {
                            $twitterAction->increment('tagged_post');
                        }
                    }

                    if($likesCount > 0){

                        $totalLikesPoints =self::updatePointsForLikes($account, $likesCount, $totalLikesPoints, $result['id']);

                    }

                    if($impressionCount > 100){

                        $totalImpressionsPoints = self::updatePointsForImpressions($account, $impressionCount, $totalImpressionsPoints, $result['id']);
                    }

                    if($retweetCount > 0){
                        $totalRetweetPoints = self::updatePointsForRetweets($account, $retweetCount, $totalRetweetPoints, $result['id']);
                    }

                    $authorPosts[$authorId][$tweetDate] = ($authorPosts[$authorId][$tweetDate] ?? 0) + 1;

                    // Check if this author has posted daily throughout the week
                    if (count($authorPosts[$authorId]) === 6) {
                        $activeAuthors[] = $authorId;
                    }

                    $processedAuthors[$authorId] = isset($processedAuthors[$authorId]) ? $processedAuthors[$authorId] + 1 : 1;

                }

            }
            if (isset($results['meta']['next_token'])) {
                $results = self::getPosts($this->apiKey, $results['meta']['next_token']);
            }else{
                break;
            }
        }

        foreach ($activeAuthors as $authorId) {
            $authorAccount = Account::where('twitter_id', $authorId)->first();
            $queryParam = 'twitter_week';
            $existingTwitter = Twitter::where('query_param', $queryParam)
                ->where('account_id', $account->id)
                ->first();

            if ($authorAccount && !$existingTwitter) {

                $currentWeek = Week::getCurrentWeekForAccount($authorAccount);

                $twitter = new Twitter([
                    'account_id'=>$authorAccount->id,
                    'claim_points' => ConstantValues::twitter_active_week_points, // Points for active posting throughout the week
                    'comment' => 'Активная неделя в Twitter с отметкой проекта или проектов ' . $authorId,
                    'query_param' => $queryParam
                ]);
                $currentWeek->twitters()->save($twitter);
                $currentWeek->increment('claim_points', ConstantValues::twitter_active_week_points);

            }
        }

    }

    public function getIgorProjectPosts()
    {
        $processedAuthors = [];

        $results = self::getIgorsPosts($this->apiKey);

        $totalLikesPoints = 0;
        $totalImpressionsPoints = 0;
        $totalRetweetPoints = 0;

        $authorPosts = []; // Store the number of posts for each author
        $activeAuthors = []; // Store authors with daily posts throughout the week

        while(isset($results['data'])){
            foreach ($results['data'] as $result) {
//                dd($results['data']);
                $likesCount = $result['public_metrics']['like_count'] ?? 0;

                $impressionCount = $result['public_metrics']['impression_count'] ?? 0;

                $retweetCount = $result['public_metrics']['retweet_count'] ?? 0;

                $authorId = $result['author_id'];

                $tweetDate = Carbon::parse($result['created_at'])->format('Y-m-d');

                $account = Account::where('twitter_id', $result['author_id'])->first();

                if ($account && (!isset($processedAuthors[$authorId]) || $processedAuthors[$authorId] <= 7)) {

                    $currentWeek = Week::getCurrentWeekForAccount($account);

                    $queryParam = 'twitter_post_twitter_id' . $result['id'];

                    $existingTwitter = Twitter::where('query_param', $queryParam)
                        ->where('account_id', $account->id)
                        ->first();

                    if(!$existingTwitter){
                        $twitter = new Twitter([
                            'account_id'=>$account->id,
                            'claim_points' => ConstantValues::twitter_igor_tweet,
                            'comment' => 'Пост с отметкой Игоря https://twitter.com/igor_3000A post_id=' . $result['id'],
                            'query_param' => $queryParam
                        ]);
                        $currentWeek->twitters()->save($twitter);
                        $currentWeek->increment('claim_points', ConstantValues::twitter_igor_tweet);

                        $twitterAction = $account->getTwitterAction();
                        if ($twitterAction) {
                            $twitterAction->increment('tagged_post');
                        }
                    }

                    if($likesCount > 0){

                        $totalLikesPoints =self::updatePointsForLikes($account, $likesCount, $totalLikesPoints, $result['id']);

                    }

                    if($impressionCount > 100){

                        $totalImpressionsPoints = self::updatePointsForImpressions($account, $impressionCount, $totalImpressionsPoints, $result['id']);
                    }

                    if($retweetCount > 0){
                        $totalRetweetPoints = self::updatePointsForRetweets($account, $retweetCount, $totalRetweetPoints, $result['id']);
                    }

                    $authorPosts[$authorId][$tweetDate] = ($authorPosts[$authorId][$tweetDate] ?? 0) + 1;

                    // Check if this author has posted daily throughout the week
                    if (count($authorPosts[$authorId]) === 6) {
                        $activeAuthors[] = $authorId;
                    }

                    $processedAuthors[$authorId] = isset($processedAuthors[$authorId]) ? $processedAuthors[$authorId] + 1 : 1;

                }

            }
            if (isset($results['meta']['next_token'])) {
                $results = self::getIgorsPosts($this->apiKey, $results['meta']['next_token']);
            }else{
                break;
            }
        }

        foreach ($activeAuthors as $authorId) {
            $authorAccount = Account::where('twitter_id', $authorId)->first();
            if ($authorAccount) {

                $currentWeek = Week::getCurrentWeekForAccount($authorAccount);

                $twitter = new Twitter([
                    'account_id'=>$authorAccount->id,
                    'claim_points' => ConstantValues::twitter_active_week_points, // Points for active posting throughout the week
                    'comment' => 'Активная неделя в Twitter с отметкой проекта или проектов ' . $authorId,
                    'query_param' => 'twitter_week'
                ]);
                $currentWeek->twitters()->save($twitter);
                $currentWeek->increment('claim_points', ConstantValues::twitter_active_week_points);
            }
        }
    }

    //27.02
    public function get3TwitterAccountsTweetsAndAnalytics()
    {
        $results = self::getProjectsTweets($this->apiKey);


        $tweet_ids = [];

        $likesCountPerAccount = [];

        $retweetsCountPerAccount = [];

        $quotesCountPerAccount = [];

        $commentsCountPerAccount = [];

        while(isset($results['data'])){
            foreach ($results['data'] as $result) {
                $tweet_ids[] = $result['id'];
            }
            if (isset($results['meta']['next_token'])) {
                $results = self::getProjectsTweets($this->apiKey, $results['meta']['next_token']);
            }else{
                break;
            }
        }

        if(isset($tweet_ids) && count($tweet_ids)>0){

            $allAccounts = Account::all()->keyBy('twitter_id');
            foreach ($tweet_ids as $id){
                $likesResult = self::get3ProjectsLikingUsers($this->apiKey, $id);
                sleep(1);

                while (isset($likesResult['data'])) {
                    foreach ($likesResult['data'] as $likedUser) {

                        $twitterId = $likedUser['id'];

                        if (isset($allAccounts[$twitterId]) && (!isset($likesCountPerAccount[$twitterId]) || $likesCountPerAccount[$twitterId] < 604)) {
                            $account = $allAccounts[$twitterId];
                            // model method
                            $account->incrementPoints('likes', $id);

                            if (!isset($likesCountPerAccount[$twitterId])) {
                                $likesCountPerAccount[$twitterId] = 1;
                            } else {
                                $likesCountPerAccount[$twitterId]++;
                            }
                        }
                    }
                    if (isset($likesResult['meta']['next_token'])) {
                        $likesResult = self::get3ProjectsLikingUsers($this->apiKey, $id, $likesResult['meta']['next_token']);
                    }else{
                        break;
                    }
                }

                $retweetsResult = self::get3ProjectsRetweetingUsers($this->apiKey, $id);
                while(isset($retweetsResult['data'])){
                    sleep(1);
                    foreach ($retweetsResult['data'] as $retweetedUser) {
                        $twitterId = $retweetedUser['id'];

                        if (isset($allAccounts[$twitterId]) && (!isset($retweetsCountPerAccount[$twitterId]) || $retweetsCountPerAccount[$twitterId] < 7)) {
                            $account = $allAccounts[$twitterId];
                            // model method
                            $account->incrementPoints('retweets', $id);

                            if (!isset($retweetsCountPerAccount[$twitterId])) {
                                $retweetsCountPerAccount[$twitterId] = 1;
                            } else {
                                $retweetsCountPerAccount[$twitterId]++;
                            }
                        }
                    }
                    if (isset($retweetsResult['meta']['next_token'])) {
                        $retweetsResult = self::get3ProjectsLikingUsers($this->apiKey, $id, $retweetsResult['meta']['next_token']);
                    }else{
                        break;
                    }
                }

                $quotesResult = self::get3ProjectsQuotesUsers($this->apiKey, $id);
                while(isset($quotesResult['data'])){
                    sleep(1);
                    foreach ($quotesResult['data'] as $quotedUser) {
//                        $twitterId = $quotedUser['id'];
                        $twitterId = $quotedUser['author_id'];
                        $quoteId = $quotedUser['id'];

                        if (isset($allAccounts[$twitterId]) && (!isset($quotesCountPerAccount[$twitterId]) || $quotesCountPerAccount[$twitterId] < 7)) {
                            $account = $allAccounts[$twitterId];
                            // model method
                            $account->incrementPoints('quotes', $id, $quoteId);

                            if (!isset($quotesCountPerAccount[$twitterId])) {
                                $quotesCountPerAccount[$twitterId] = 1;
                            } else {
                                $quotesCountPerAccount[$twitterId]++;
                            }
                        }
                    }
                    if (isset($quotesResult['meta']['next_token'])) {
                        $quotesResult = self::get3ProjectsQuotesUsers($this->apiKey, $id, $quotesResult['meta']['next_token']);
                    }else{
                        break;
                    }
                }

                $commentsResult = self::get3ProjectsCommentsUsers($this->apiKey, $id);
                while(isset($commentsResult['data'])){
                    sleep(1);
                    foreach ($commentsResult['data'] as $commentedUser) {
                        $twitterId = $commentedUser['author_id'];
                        $commentId = $commentedUser['id'];

                        if (isset($allAccounts[$twitterId]) && (!isset($commentsCountPerAccount[$twitterId]) || $commentsCountPerAccount[$twitterId] < 21)) {
                            $account = $allAccounts[$twitterId];
                            // model method
                            $account->incrementPoints('comments', $id, $commentId);

                            if (!isset($commentsCountPerAccount[$twitterId])) {
                                $commentsCountPerAccount[$twitterId] = 1;
                            } else {
                                $commentsCountPerAccount[$twitterId]++;
                            }
                        }
                    }
                    if (isset($commentsResult['meta']['next_token'])) {
                        $commentsResult = self::get3ProjectsCommentsUsers($this->apiKey, $id, $commentsResult['meta']['next_token']);
                    }else{
                        break;
                    }
                }
            }
        }
    }

    public function updatePointsForRetweet(): void
    {
        $tweetIds = DB::table('twitter_collabs')
            ->pluck('tweet_id')
            ->toArray();
        $accountsTwitterIds = DB::table('accounts')
            ->whereNotNull('twitter_id')
            ->pluck('twitter_id')
            ->toArray();

        if(count($accountsTwitterIds) > 0){
            foreach ($tweetIds as $tweetId){
                sleep(3);
                $retweetedIds = $this->getTweetCollabRetweets($this->apiKey, $tweetId);
                var_dump($retweetedIds);
                foreach ($accountsTwitterIds as $twitter_id){
                    if(in_array($twitter_id, $retweetedIds)){
                        var_dump($tweetId);
                        $account = Account::where('twitter_id', $twitter_id)->first();
                        $this->incrementPointsForTwitterCollab($tweetId, $account);
                    }
                }
            }
        }
    }

    private function getProjectsTweets(string $apiKey, ?string $paginationToken = null)
    {
        $url = 'https://api.twitter.com/2/tweets/search/recent?query=(from:igor_3000A -is:retweet -is:reply) OR (from:SafeSoulETH -is:retweet -is:reply) OR (from:SoulsClubETH -is:retweet -is:reply)&tweet.fields=id,text,author_id,created_at&max_results=100';

        if ($paginationToken) {
            $url .= ('&pagination_token=' . $paginationToken);
        }

        try {
            $result = Http::withToken($apiKey)->get($url);


            if ($result->ok()) {
                Log::info($result->json());
                return $result->json();
            }
        } catch (\Exception $e) {

            Log::error('Twitter API request failed: ' . $e->getMessage());
        }

        return [];
    }

    private function get3ProjectsLikingUsers(string $apiKey, string $tweet_id, ?string $paginationToken = null)
    {
        $url = "https://api.twitter.com/2/tweets/$tweet_id/liking_users?max_results=100";
        Log::info($url);
        if ($paginationToken) {
            $url .= ('&pagination_token=' . $paginationToken);
        }

        try {
            $result = Http::withToken($apiKey)->get($url);

            if ($result->ok()) {
                Log::info($result->json());
                return $result->json();
            }
        } catch (\Exception $e) {

            Log::error('Twitter API request failed: ' . $e->getMessage());
        }
    }

    private function get3ProjectsRetweetingUsers(string $apiKey, string $tweet_id, ?string $paginationToken = null)
    {
        $url = "https://api.twitter.com/2/tweets/$tweet_id/retweeted_by?max_results=100";
        Log::info($url);
        if ($paginationToken) {
            $url .= ('&pagination_token=' . $paginationToken);
        }

        try {
            $result = Http::withToken($apiKey)->get($url);

            if ($result->ok()) {
                Log::info($result->json());
                return $result->json();
            }
        } catch (\Exception $e) {

            Log::error('Twitter API request failed: ' . $e->getMessage());
        }
    }

    private function get3ProjectsQuotesUsers(string $apiKey, string $tweet_id, ?string $paginationToken = null)
    {
        $isLoop = true;
        $maxAttempts = 5;
        $attempts = 0;

        while ($isLoop && $attempts < $maxAttempts) {
            $url = "https://api.twitter.com/2/tweets/$tweet_id/quote_tweets?max_results=100&exclude=retweets,replies&expansions=author_id";

            Log::info($url);
            if ($paginationToken) {
                $url .= ('&pagination_token=' . $paginationToken);
            }

            var_dump($url);

            try {
                $response = Http::withToken($apiKey)->get($url);
                Log::info(json_encode($response));

                if ($response->ok()) {
                    $isLoop = false;
                    Log::info(json_encode($response));
                    return $response->json();
                } else {
                    if ($response->status() == 429) {
                        $rateLimitReset = $response->header('x-rate-limit-reset');
                        if ($rateLimitReset) {
                            $resetTime = Carbon::createFromTimestamp($rateLimitReset);
                            $waitTime = $resetTime->diffInSeconds(Carbon::now());
                            Log::info("Rate limit hit. Will reset at: " . $resetTime->toDateTimeString() . ". Waiting for $waitTime seconds.");
                            sleep($waitTime);
                        } else {
                            Log::error("Rate limit hit but no reset time found.");
                            $isLoop = false; // Break the loop if we cannot find the reset time
                        }
                    } else {
                        Log::error("Unexpected error: " . $response->status());
                        $isLoop = false; // Break the loop on other errors
                    }
                }
            } catch (\Exception $e) {
                Log::error('Twitter API request failed: ' . $e->getMessage());
                $isLoop = false; // Break the loop on exceptions
            }

            $attempts++;
        }

        return null; // Return null if all attempts fail
    }

    private function get3ProjectsCommentsUsers(string $apiKey, string $tweet_id, ?string $paginationToken = null)
    {
        $url = "https://api.twitter.com/2/tweets/search/recent?query=conversation_id:$tweet_id&tweet.fields=id,text,author_id,created_at&max_results=100";

        Log::info($url);
        if ($paginationToken) {
            $url .= ('&pagination_token=' . $paginationToken);
        }

        try {
            $result = Http::withToken($apiKey)->get($url);

            if ($result->ok()) {
                Log::info($result->json());
                return $result->json();
            }
        } catch (\Exception $e) {

            Log::error('Twitter API request failed: ' . $e->getMessage());
        }
    }


    //27.02

    private static function getPosts(string $apiKey, ?string $paginationToken = null)
    {
//        $currentDate = date('Y-m-d');
//        $previousDate = date('Y-m-d', strtotime('-1 day', strtotime($currentDate)));
//        dd($previousDate);

        $url = 'https://api.twitter.com/2/tweets/search/recent?query=@SafeSoulETH -is:retweet -is:reply OR @SoulsClubETH -is:retweet -is:reply&max_results=100&tweet.fields=id,text,created_at,public_metrics&user.fields=id,name&expansions=author_id';
        Log::info($url);
        if ($paginationToken) {
            $url .= ('&pagination_token=' . $paginationToken);
        }

        try {
            $result = Http::withToken($apiKey)->get($url);

            if ($result->ok()) {
                Log::info($result->json());
                return $result->json();
            }
        } catch (\Exception $e) {

            Log::error('Twitter API request failed: ' . $e->getMessage());
        }

        return [];
    }

    private static function getIgorsPosts(string $apiKey, ?string $paginationToken = null)
    {
        $url = 'https://api.twitter.com/2/tweets/search/recent?query=@igor_3000A -is:retweet -is:reply&max_results=100&tweet.fields=id,text,created_at,public_metrics&user.fields=id,name&expansions=author_id';

        if ($paginationToken) {
            $url .= ('&pagination_token=' . $paginationToken);
        }
        Log::info($url);
        try {
            $result = Http::withToken($apiKey)->get($url);

            if ($result->ok()) {
                Log::info($result->json());
                return $result->json();
            }
        } catch (\Exception $e) {

            Log::error('Twitter API request failed: ' . $e->getMessage());
        }

        return [];
    }

    private static function updatePointsForLikes($account, $likesCount, $totalLikesPoints, $tweet_id)
    {
        $queryParam = 'twitter_post_likes_tweet_id' . $tweet_id;
        $pointsForLikes = min($likesCount * ConstantValues::twitter_project_like_point, ConstantValues::twitter_project_max_likes_points - $totalLikesPoints);

        $currentWeek = Week::getCurrentWeekForAccount($account);

        $existingTwitter = Twitter::where('query_param', $queryParam)
            ->where('account_id', $account->id)
            ->first();

        if(!$existingTwitter){
            $twitter = new Twitter([
                'account_id'=> $account->id,
                'claim_points' => $pointsForLikes,
                'comment' => 'за каждый лайк под твоим постом c отметкой любой из страниц https://twitter.com/SoulsClubETH https://twitter.com/SafeSoulETH https://twitter.com/igor_3000A' . 'likes_count: ' . $likesCount,
                'query_param' => $queryParam
            ]);

            $currentWeek->twitters()->save($twitter);

            $totalLikesPoints += $pointsForLikes;

            $currentWeek->increment('claim_points', $pointsForLikes);
        }


        return $totalLikesPoints;
    }

    private static function updatePointsForImpressions($account, $impressionCount, $totalImpressionsPoints, $tweet_id)
    {
        $queryParam = 'twitter_impressions_tweet_id' . $tweet_id;

        $pointsForImpressions = min($impressionCount * ConstantValues::twitter_project_impression_point, ConstantValues::twitter_project_max_impression_point - $totalImpressionsPoints);

        $currentWeek = Week::getCurrentWeekForAccount($account);

        $existingTwitter = Twitter::where('query_param', $queryParam)
            ->where('account_id', $account->id)
            ->first();
        if(!$existingTwitter){
            $twitter = new Twitter([
                'account_id'=> $account->id,
                'claim_points' => $pointsForImpressions,
                'comment' => 'за каждые 100 просмотров этого твита https://twitter.com/SoulsClubETH https://twitter.com/SafeSoulETH https://twitter.com/igor_3000A' . ' impressions_count ' . $impressionCount,
                'query_param' => $queryParam
            ]);

            $currentWeek->twitters()->save($twitter);

            $totalImpressionsPoints += $pointsForImpressions;

            $currentWeek->increment('claim_points', $pointsForImpressions);
        }

        return $totalImpressionsPoints;
    }

    private static function updatePointsForRetweets($account, $retweetCount, $totalRetweetsPoints, $tweet_id)
    {
        $pointsForRetweets = min($retweetCount * ConstantValues::twitter_project_retweet_point, ConstantValues::twitter_project_max_retweet_point - $totalRetweetsPoints);

        $queryParam ='twitter_post_retweets_tweet_id' . $tweet_id;

        $currentWeek = Week::getCurrentWeekForAccount($account);

        $existingTwitter = Twitter::where('query_param', $queryParam)
            ->where('account_id', $account->id)
            ->first();

        if(!$existingTwitter){
            $twitter = new Twitter([
                'account_id'=> $account->id,
                'claim_points' => $pointsForRetweets,
                'comment' => 'за каждый репост кем-либо этого твоего твита https://twitter.com/SoulsClubETH https://twitter.com/SafeSoulETH https://twitter.com/igor_3000A' . ' retweet_count ' . $retweetCount,
                'query_param' => $queryParam
            ]);
            $currentWeek->twitters()->save($twitter);

            $totalRetweetsPoints += $pointsForRetweets;

            $currentWeek->increment('claim_points', $pointsForRetweets);

        }

        return $totalRetweetsPoints;
    }

    private function getTweetCollabRetweets(string $apiKey, $tweetId)
    {

        $retweetedIds = [];
        $paginationToken = null;
        do {
            try {
                $url = 'https://api.twitter.com/2/tweets/'. $tweetId. '/retweeted_by';



                if ($paginationToken !== null) {
                    sleep(3);
                    $url .= ('&pagination_token=' . $paginationToken);
                }

                $result = Http::withToken($apiKey)->get($url);

                if ($result->ok()) {

                    Log::info($result->json());
                    $results = $result->json();
                    if(isset($results['data'])){
                        foreach ($results['data'] as $result){
                            if(isset($result['id'])){
                                $retweetedIds[] = $result['id'];
                            }
                        }
                    }
                    $paginationToken = $results['next_cursor'] ?? null;
                }


            }catch (\Exception $e){
                Log::error('Twitter API request failed: ' . $e->getMessage());
            }

        }while($paginationToken);
        return $retweetedIds;
    }

    private function incrementPointsForTwitterCollab(string $tweet_id, $account)
    {

        $currentWeek = Week::getCurrentWeekForAccount($account);

        $queryParam = 'twitter_collab_'. $tweet_id;

        $existingTwitter = Twitter::where('query_param', $queryParam)
            ->where('account_id', $account->id)
            ->first();


        if(!$existingTwitter){
            $twitter = new Twitter([
                'account_id'=>$account->id,
                'claim_points' => ConstantValues::twitter_collab_points,
                'comment' => 'rt collab post tweet_id=' . $tweet_id,
                'query_param' => $queryParam
            ]);
            $currentWeek->twitters()->save($twitter);
            $currentWeek->increment('claim_points', ConstantValues::twitter_collab_points);
        }
    }

//https://api.twitter.com/2/tweets/search/recent?query=from:igor_3000A -is:retweet -is:reply&tweet.fields=id,text,author_id,created_at&max_results=100


}
