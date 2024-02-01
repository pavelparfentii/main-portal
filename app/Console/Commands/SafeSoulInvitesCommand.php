<?php

namespace App\Console\Commands;

use App\Traits\SafeSoulTrait;
use Illuminate\Console\Command;

class SafeSoulInvitesCommand extends Command
{
    use SafeSoulTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:safe-soul-invites-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'gets all invites from safesoul';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getInvites();
        $this->info('success');
    }
}
