<?php

namespace App\Console\Commands;

use App\Traits\TwitterTrait;
use Illuminate\Console\Command;

class UpdateTwitterCollabPoints extends Command
{
    use TwitterTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-twitter-collab-points';

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
        $this->updatePointsForRetweet();
        $this->info('success');

    }
}
