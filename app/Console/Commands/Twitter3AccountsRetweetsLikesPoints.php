<?php

namespace App\Console\Commands;

use App\Traits\TwitterTrait;
use Illuminate\Console\Command;

class Twitter3AccountsRetweetsLikesPoints extends Command
{
    use TwitterTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:twitter3-accounts-retweets-likes-points';

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
        $this->get3TwitterAccountsTweetsAndAnalytics();
        $this->info('success');
    }
}
