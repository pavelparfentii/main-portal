<?php

namespace App\Console\Commands;

use App\Traits\TwitterPartnerTrait;
use Illuminate\Console\Command;

class SafesoulUpdatePointsForTwitterPartnerFollowing extends Command
{
    use TwitterPartnerTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:safesoul-update-points-for-twitter-partner-following';

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
        $this->updatePointsForTwitterFollowing();
        $this->info('success');
    }
}
