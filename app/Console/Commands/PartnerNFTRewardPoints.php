<?php

namespace App\Console\Commands;

use App\Traits\PartnerTrait;
use Illuminate\Console\Command;

class PartnerNFTRewardPoints extends Command
{
    use PartnerTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:partner-n-f-t-reward-points';

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
        $this->checkTokensOwnership();
        $this->info('success');
    }
}
