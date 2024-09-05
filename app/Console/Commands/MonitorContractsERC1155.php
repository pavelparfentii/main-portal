<?php

namespace App\Console\Commands;

use App\Events\FarmingNFTUpdated;
use App\Models\FarmingCollection;
use App\Models\FarmingNFT;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class MonitorContractsERC1155 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-contracts-e-r-c1155';

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
//        $contractAddresses = FarmingCollection::whereNotNull('token_id')->get();
        $contractAddresses = FarmingCollection::whereNotNull('token_id')->select('contract_address', 'infura_key')
            ->distinct()
            ->get();

        foreach ($contractAddresses as $contractAddress){
            $processes[$contractAddress->infura_key] = new Process(['node', './node/contractFor/monitorERC1155.js', $contractAddress->infura_key, $contractAddress->contract_address]);
        }

        foreach ($processes as $name => $process) {
            $process->setTimeout(null); // Prevent the process from timing out
            $process->start();

            $this->info("Started process: $name");
        }

        while (true) {
            foreach ($processes as $name => $process) {
                if ($process->isRunning()) {
                    // Read and display real-time output
                    $output = $process->getIncrementalOutput();
                    if ($output) {
                        $this->info("[$name] $output");
                        $this->handleOutput($name, $output);
                    }

                    $errorOutput = $process->getIncrementalErrorOutput();
                    if ($errorOutput) {
                        $this->error("[$name] $errorOutput");
                    }
                } else {
                    // Restart the process if it has stopped
                    $this->warn("Process $name stopped. Restarting...");
                    $process->start();
                }
            }

            // Sleep for a short period to avoid high CPU usage
            usleep(100000); // 0.1 second
        }
    }

    protected function handleOutput(string $name, string $output)
    {
        // Split the output by newline to handle multiple JSON objects
        $lines = explode(PHP_EOL, $output);

        foreach ($lines as $line) {

            if (empty($line)) {
                continue;
            }

            // Decode the JSON line
            $eventData = json_decode($line, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($eventData['contractAddress'], $eventData['from'], $eventData['to'], $eventData['token'], $eventData['value'], $eventData['transfer'])) {
                $this->info($eventData['token']);
                $this->updateAppEndpoint($eventData);
            }elseif (json_last_error() === JSON_ERROR_NONE && isset($eventData['contractAddress'], $eventData['from'], $eventData['to'], $eventData['tokens'], $eventData['values'], $eventData['transfer'])){
                $this->info($eventData);
            }
            else {
                // Log error or handle invalid output
                $this->error("Invalid JSON output from process: $line");
            }
        }
    }

    protected function updateAppEndpoint(array $eventData)
    {

        $collection = FarmingCollection::where('contract_address', $eventData['contractAddress'])
            ->where('token_id', $eventData['token'])
            ->first();

        if($collection){
            $itemPointsDaily = round($collection->farm_points_weekly / 7, 3);

            $contractAddress = strtolower($eventData['contractAddress']);
            $fromAddress = strtolower($eventData['from']);
            $toAddress = strtolower($eventData['to']);

            $tokenId = $eventData['token'];
            $tokenAmount = (int)$eventData['value'];

            $collectionToken = DB::table('farming_collections')
                ->where('token_id', $tokenId)
                ->first();

            if ($collectionToken) {
                //
                $loser = DB::table('farming_n_f_t_s')
                    ->where('contract_address', $contractAddress)
                    ->where('holder', $fromAddress)
                    ->where('token_id', $tokenId)
                    ->first();

                if (!$loser) {
                    DB::table('farming_n_f_t_s')->insert([
                        'contract_address' => $contractAddress,
                        'holder' => $fromAddress,
                        'token_balance' => 0,
                        'token_balance_last_update' => now(),
                        'item_points_daily' => $itemPointsDaily,
                        'farm_points_daily_total' => 0,
                        'token_id'=>$tokenId
                    ]);

                    $loser = DB::table('farming_n_f_t_s')
                        ->where('contract_address', $contractAddress)
                        ->where('holder', $fromAddress)
                        ->where('token_id', $tokenId)
                        ->first();

                }

                DB::table('farming_n_f_t_s')
                    ->where('id', $loser->id)
                    ->where('token_id', $tokenId)
                    ->decrement('token_balance', $tokenAmount);

                FarmingNFTUpdated::dispatch($loser->id, null, null);

//                $loser = DB::table('farming_n_f_t_s')
//                    ->where('id', $loser->id)
//                    ->first();
//
//                $farmingNFT = FarmingNFT::find($loser->id);
//                $accountExists = DB::table('accounts')
//                    ->where('wallet', $farmingNFT->holder)
//                    ->exists();
//
//                if ($loser->token_balance == 0 || $loser->token_balance < 0) {
//                    if($accountExists){
//                        DB::table('farming_n_f_t_s')
//                            ->where('id', $loser->id)
//                            ->update([
//                                'token_balance_last_update' => now(),
//                                'item_points_daily' => $itemPointsDaily,
//                                'farm_points_daily_total' => 0,
//                            ]);
//                    }else{
//                        DB::table('farming_n_f_t_s')
//                            ->where('id', $loser->id)
//                            ->delete();
//                    }
//
//
//                } else {
//                    DB::table('farming_n_f_t_s')
//                        ->where('id', $loser->id)
//                        ->update([
//                            'token_balance_last_update' => now(),
//                            'item_points_daily' => $itemPointsDaily,
//                            'farm_points_daily_total' => round($loser->token_balance * $itemPointsDaily, 3),
//                        ]);
//                }

//                if($farmingNFT){
//                    $farmingNFT->refresh();
//                    FarmingNFTUpdated::dispatch($farmingNFT);
//                }

                // Handle winner updates
                $winner = DB::table('farming_n_f_t_s')
                    ->where('contract_address', $contractAddress)
                    ->where('holder', $toAddress)
                    ->where('token_id', $tokenId)
                    ->first();

                if (!$winner) {
                    $winnerId = DB::table('farming_n_f_t_s')->insertGetId([
                        'contract_address' => $contractAddress,
                        'holder' => $toAddress,
                        'token_balance' => 1,
                        'token_balance_last_update' => now(),
                        'item_points_daily' => $itemPointsDaily,
                        'farm_points_daily_total' => $itemPointsDaily,
                        'token_id'=>$tokenId
                    ]);
                    FarmingNFTUpdated::dispatch($winnerId, null, null);

                } else {
                    DB::table('farming_n_f_t_s')
                        ->where('id', $winner->id)
                        ->where('token_id', $tokenId)
                        ->increment('token_balance', 1);

                    $winner = DB::table('farming_n_f_t_s')
                        ->where('id', $winner->id)
                        ->first();

                    DB::table('farming_n_f_t_s')
                        ->where('id', $winner->id)
                        ->update([
                            'token_balance_last_update' => now(),
                            'item_points_daily' => $itemPointsDaily,
                            'farm_points_daily_total' => round($winner->token_balance * $itemPointsDaily, 3),
                        ]);
                    FarmingNFTUpdated::dispatch($winner->id, null, null);
                }

            }
            $this->info('Successfully updated the app endpoint with transfer event data.');
        }


    }
}
