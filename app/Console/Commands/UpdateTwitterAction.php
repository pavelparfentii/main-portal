<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTwitterAction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-twitter-action';

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
        $accounts = Account::cursor();

        foreach ($accounts as $account){
           $twitterAction = $account->getTwitterAction();

           $likesCount = DB::table('twitters')->where('account_id', $account->id)
               ->where(function ($query) {
                   $query->where('query_param', '3projects_likes')
                       ->orWhere('query_param', 'like', '3projects_likes_%');
               })
               ->count();
           if($likesCount > 0){
               $account->action()->update(['likes'=>$likesCount]);
//               $twitterAction->update(['likes'=>$likesCount]);
           }

           $retweetsCount = DB::table('twitters')
               ->where('account_id', $account->id)
               ->where(function ($query) {
                   $query->where('query_param', '3projects_retweets')
                       ->orWhere('query_param', 'like', '3projects_retweets_%');
               })

               ->count();
           if($retweetsCount && $retweetsCount > 0){
//               $twitterAction->update(['retweets'=>$retweetsCount]);
               $account->action()->update(['retweets'=>$retweetsCount]);
           }

           $commentsCount = DB::table('twitters')
               ->where('account_id', $account->id)
               ->where(function ($query) {
                   $query->where('query_param', '3projects_comments')
                       ->orWhere('query_param', 'like', '3projects_comments_%');
               })
               ->count();
            if($commentsCount && $commentsCount > 0){
//                $twitterAction->update(['comments'=>$commentsCount]);
                $account->action()->update(['comments'=>$commentsCount]);
            }

            $quotesCount = DB::table('twitters')
                ->where('account_id', $account->id)
                ->where(function ($query) {
                   $query->where('query_param', '3projects_quotes')
                        ->orWhere('query_param', 'like', '3projects_quotes%');
                })
                ->count();
            if($quotesCount && $quotesCount > 0){
//                $twitterAction->update(['quotes'=>$quotesCount]);
                $account->action()->update(['quotes'=>$quotesCount]);
            }

            $twitterPost = DB::table('twitters')
                ->where('account_id', $account->id)
                ->where(function ($query) {
                    $query->where('query_param', 'twitter_post')
                        ->orWhere('query_param', 'like', 'twitter_post_twitter%');
                })
                ->count();

            if($twitterPost && $twitterPost > 0){
//                $twitterAction->update(['quotes'=>$quotesCount]);
                $account->action()->update(['tagged_post'=>$twitterPost]);
            }

        }
        $this->info('success');
    }
}
