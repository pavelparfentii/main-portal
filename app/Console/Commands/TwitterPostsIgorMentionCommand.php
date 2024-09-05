<?php

namespace App\Console\Commands;

use App\Traits\TwitterTrait;
use Illuminate\Console\Command;

class TwitterPostsIgorMentionCommand extends Command
{
    use TwitterTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:twitter-posts-igor-mention-command';

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
        $this->getIgorProjectPosts();
        $this->info('success');
    }
}
