<?php

namespace App\Console\Commands;

use App\Traits\SafeSoulTrait;
use Illuminate\Console\Command;

class UpdateUserDataCommand extends Command
{
    use SafeSoulTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get accounts update daily';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getAccountsUpdate();
        $this->info('success');
        return;
    }
}
