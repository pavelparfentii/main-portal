<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class MonitorContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-contracts';

    protected $description = 'Monitor multiple contracts for transfers';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The console command description.
     *
     * @var string
     */

    /**
     * Execute the console command.
     */

        public function handle()
    {
        $contractAddresses = [
            '0x7f36182dee28c45de6072a34d29855bae76dbe2f',
            '0x60e4d786628fea6478f785a6d7e704777c86a7c6',

            // Add more addresses as needed
        ];

        $processes = [];

        foreach ($contractAddresses as $address) {
            var_dump(base_path('node/contractFor/monitor.js'));
            $process = new Process(['node', base_path('node/contractFor/monitor.js'), $address]);
            $process->setTimeout(0);
            $process->start();

            $processes[$address] = $process;
        }

        foreach ($processes as $address => $process) {
            $process->wait(function ($type, $buffer) use ($address) {
                if (Process::ERR === $type) {
                    $this->error("[$address] $buffer");
                } else {
                    $this->info("[$address] $buffer");
                }
            });
        }
    }
}
