<?php

namespace App\Console\Commands;

use App\Traits\DigitalAnimalsTrait;
use Illuminate\Console\Command;

class DigitalAnimalsLord20Command extends Command
{
    use DigitalAnimalsTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:digital-animals-lord20-command';

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
        $this->lordPoints();
        $this->info('success');
        return;
    }
}
