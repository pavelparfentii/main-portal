<?php

namespace App\Console\Commands;

use App\Traits\SafeSoulTrait;
use Illuminate\Console\Command;

class GetDiscordRolesForAccountsCommand extends Command
{
    use SafeSoulTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-discord-roles-for-accounts-command';

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
        $this->getDiscordRolesWithNamesForAccounts();
        $this->info('success');
    }
}
