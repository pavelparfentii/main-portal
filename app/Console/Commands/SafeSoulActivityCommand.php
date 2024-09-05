<?php

namespace App\Console\Commands;

use App\Traits\SafeSoulTrait;
use Illuminate\Console\Command;

class SafeSoulActivityCommand extends Command
{
    use SafeSoulTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:safe-soul-activity-command';

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
        $this->getActivity();
        $this->info('success');
    }
}
