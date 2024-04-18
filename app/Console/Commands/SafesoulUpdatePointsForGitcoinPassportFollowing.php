<?php

namespace App\Console\Commands;

use App\Traits\SafeSoulTrait;
use Illuminate\Console\Command;

class SafesoulUpdatePointsForGitcoinPassportFollowing extends Command
{
    use SafeSoulTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:safesoul-update-points-for-gitcoin-passport-following';

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
        $this->updateGitcoinPointsForGitcoinTwitterFollowing();
        $this->info('success');
    }
}
