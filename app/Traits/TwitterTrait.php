<?php


namespace App\Traits;


use App\ConstantValues;
use App\Models\Account;
use App\Models\Twitter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait TwitterTrait
{
    private string $apiKey = 'AAAAAAAAAAAAAAAAAAAAAPLJrwEAAAAAsRCBQCV7mGPbX%2BMOBABwNYkVR68%3DlSEEjoj6v2V9SsLXXIylvVw1RUXygxAxRwSQGhxe3ldZ0CrYEG';

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
                    $twitter = new Twitter([
                        'points' => ConstantValues::twitter_project_tweet,
                        'comment' => 'Пост в Twitter с отметкой проекта или проектов post_id=' . $result['id'],
                        'query_param' => 'twitter_post'
                    ]);
                    $account->twitters()->save($twitter);

                    if($likesCount > 0){

                        $totalLikesPoints =self::updatePointsForLikes($account, $likesCount, $totalLikesPoints);

                    }

                    if($impressionCount > 100){

                        $totalImpressionsPoints = self::updatePointsForImpressions($account, $impressionCount, $totalImpressionsPoints);
                    }

                    if($retweetCount > 0){
                        $totalRetweetPoints = self::updatePointsForRetweets($account, $retweetCount, $totalRetweetPoints);
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
            if ($authorAccount) {
                $twitter = new Twitter([
                    'points' => ConstantValues::twitter_active_week_points, // Points for active posting throughout the week
                    'comment' => 'Активная неделя в Twitter с отметкой проекта или проектов ' . $authorId,
                    'query_param' => 'twitter_week'
                ]);
                $authorAccount->twitters()->save($twitter);
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
                    $twitter = new Twitter([
                        'points' => ConstantValues::twitter_igor_tweet,
                        'comment' => 'Пост с отметкой Игоря https://twitter.com/igor_3000A post_id=' . $result['id'],
                        'query_param' => 'twitter_post'
                    ]);
                    $account->twitters()->save($twitter);

                    if($likesCount > 0){

                        $totalLikesPoints =self::updatePointsForLikes($account, $likesCount, $totalLikesPoints);

                    }

                    if($impressionCount > 100){

                        $totalImpressionsPoints = self::updatePointsForImpressions($account, $impressionCount, $totalImpressionsPoints);
                    }

                    if($retweetCount > 0){
                        $totalRetweetPoints = self::updatePointsForRetweets($account, $retweetCount, $totalRetweetPoints);
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
                $twitter = new Twitter([
                    'points' => ConstantValues::twitter_active_week_points, // Points for active posting throughout the week
                    'comment' => 'Активная неделя в Twitter с отметкой проекта или проектов ' . $authorId,
                    'query_param' => 'twitter_week'
                ]);
                $authorAccount->twitters()->save($twitter);
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
                            $account->incrementPoints('likes', $twitterId);

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
                            $account->incrementPoints('retweets', $twitterId);

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
                            $account->incrementPoints('quotes', $twitterId, $quoteId);

                            if (!isset($quotesCountPerAccount[$twitterId])) {
                                $quotesCountPerAccount[$twitterId] = 1;
                            } else {
                                $quotesCountPerAccount[$twitterId]++;
                            }
                        }
                    }
                    if (isset($quotesResult['meta']['next_token'])) {
                        $quotesResult = self::get3ProjectsLikingUsers($this->apiKey, $id, $quotesResult['meta']['next_token']);
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
                            $account->incrementPoints('comments', $twitterId, $commentId);

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

    private function getProjectsTweets(string $apiKey, ?string $paginationToken = null)
    {
        $url = 'https://api.twitter.com/2/tweets/search/recent?query=(from:igor_3000A -is:retweet -is:reply) OR (from:SafeSoulETH -is:retweet -is:reply) OR (from:SoulsClubETH -is:retweet -is:reply)&tweet.fields=id,text,author_id,created_at&max_results=100';
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
        $url = "https://api.twitter.com/2/tweets/$tweet_id/quote_tweets?max_results=100&exclude=retweets,replies&expansions=author_id";

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

    private static function updatePointsForLikes($account, $likesCount, $totalLikesPoints)
    {
        $pointsForLikes = min($likesCount * ConstantValues::twitter_project_like_point, ConstantValues::twitter_project_max_likes_points - $totalLikesPoints);

        $twitter = new Twitter([
            'points' => $pointsForLikes,
            'comment' => 'за каждый лайк под твоим постом c отметкой любой из страниц https://twitter.com/SoulsClubETH https://twitter.com/SafeSoulETH https://twitter.com/igor_3000A' . 'likes_count: ' . $likesCount,
            'query_param' => 'twitter_post_likes'
        ]);

        $account->twitters()->save($twitter);

        $totalLikesPoints += $pointsForLikes;
        return $totalLikesPoints;
    }

    private static function updatePointsForImpressions($account, $impressionCount, $totalImpressionsPoints)
    {
        $pointsForImpressions = min($impressionCount * ConstantValues::twitter_project_impression_point, ConstantValues::twitter_project_max_impression_point - $totalImpressionsPoints);

        $twitter = new Twitter([
            'points' => $pointsForImpressions,
            'comment' => 'за каждые 100 просмотров этого твита https://twitter.com/SoulsClubETH https://twitter.com/SafeSoulETH https://twitter.com/igor_3000A' . ' impressions_count ' . $impressionCount,
            'query_param' => 'twitter_post_impressions'
        ]);

        $account->twitters()->save($twitter);

        $totalImpressionsPoints += $pointsForImpressions;
        return $totalImpressionsPoints;
    }

    private static function updatePointsForRetweets($account, $retweetCount, $totalRetweetsPoints)
    {
        $pointsForRetweets = min($retweetCount * ConstantValues::twitter_project_retweet_point, ConstantValues::twitter_project_max_retweet_point - $totalRetweetsPoints);

        $twitter = new Twitter([
            'points' => $pointsForRetweets,
            'comment' => 'за каждый репост кем-либо этого твоего твита https://twitter.com/SoulsClubETH https://twitter.com/SafeSoulETH https://twitter.com/igor_3000A' . ' retweet_count ' . $retweetCount,
            'query_param' => 'twitter_post_retweets'
        ]);

        $account->twitters()->save($twitter);

        $totalRetweetsPoints += $pointsForRetweets;
        return $totalRetweetsPoints;
    }

//https://api.twitter.com/2/tweets/search/recent?query=from:igor_3000A -is:retweet -is:reply&tweet.fields=id,text,author_id,created_at&max_results=100


}
