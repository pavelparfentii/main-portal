<?php

namespace App\Console\Commands;

use App\Traits\TwitterTrait;
use Illuminate\Console\Command;

class TwitterPostsSSMentionCommand extends Command
{
    use TwitterTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:twitter-posts-s-s-mention-command';

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
        $this->getProjectsPosts();
        $this->info('success');
    }
}
