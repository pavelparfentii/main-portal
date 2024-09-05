<?php

namespace App\Console\Commands;

use App\Traits\DigitalAnimalsTrait;
use Illuminate\Console\Command;

class DigitalAnimalsOriginalMinterCommand extends Command
{
    use DigitalAnimalsTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:digital-animals-original-minter-command';

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
        $this->getOriginalMinter();
        $this->info('success');
    }
}
