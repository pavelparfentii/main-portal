<?php

namespace App\Console\Commands;

use App\Models\OriginalMinter;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class FillOriginalMintersTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fill-original-minters-table';

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
        $bar = $this->output->createProgressBar();
        for($i = 1; $i<8889; $i++){
            $process = new Process([
                'node',
                base_path('node/getDigitalAnimalsOrigitalMinters.js'),
                $i,
            ]);
            $process->run();
            if (!$process->isSuccessful()) {

                continue;
            }

            if ($process->isSuccessful()) {
                $originalMinterOwner = json_decode($process->getOutput());


                if($originalMinterOwner !== '0x0000000000000000000000000000000000000000'){
                    $originalMinter = OriginalMinter::create([
                       'token'=>$i,
                        'wallet'=>$originalMinterOwner
                    ]);
                }else{
                    continue;
                }
            }
            $bar->advance();
        }
        $bar->finish();
    }
}
