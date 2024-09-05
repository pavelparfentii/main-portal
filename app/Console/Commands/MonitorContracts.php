<?php

namespace App\Console\Commands;

use App\Events\FarmingNFTUpdated;
use App\Models\FarmingCollection;
use App\Models\FarmingNFT;
use App\Models\PartnerNFT;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Process\Pool;
//use Illuminate\Support\Facades\Process;

class MonitorContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-contracts';

    protected $description = 'Monitor multiple contracts for transfers';

    protected array $processedOutputs = [];

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

        $contractAddresses = FarmingCollection::whereNull('token_id')->get();

//        $contractAddresses = [
//            '4e57bef968654f7295394597319cdaed' => '0x7f36182dee28c45de6072a34d29855bae76dbe2f',
//            '66f36c573c73447fba7e18ea6ce9c604' => '0x60e4d786628fea6478f785a6d7e704777c86a7c6',
//            '40892bf6b52d4e1b81daf162c9029d93' => '0xe90d8fb7b79c8930b5c8891e61c298b412a6e81a',
//            'fc9b486410e24f248a364a5c37818ef3' => '0x7ecb204fed7e386386cab46a1fcb823ec5067ad5'
//            // Add more addresses as needed
//        ];
//
//        $processes = [];
//
//        foreach ($contractAddresses as $key => $address) {
//            $processes[$key] = new Process(['node', './node/contractFor/monitor.js', $key, $address]);
//        }
        foreach ($contractAddresses as $contractAddress){
            $processes[$contractAddress->infura_key] = new Process(['node', './node/contractFor/monitor.js', $contractAddress->infura_key, $contractAddress->contract_address]);
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

        $lines = explode(PHP_EOL, $output);

        foreach ($lines as $line) {

            if (empty($line)) {
                continue;
            }

            //// Avoid processing duplicate output
            if (in_array($line, $this->processedOutputs)) {
                continue;
            }

            $this->processedOutputs[] = $line;
            ////
            // Decode the JSON line

            $eventData = json_decode($line, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($eventData['contractAddress'], $eventData['from'], $eventData['to'], $eventData['token'])) {
                $this->updateAppEndpoint($eventData);
            } else {
                // Log error or handle invalid output
                $this->error("Invalid JSON output from process: $line");
            }
        }
    }

    protected function updateAppEndpoint(array $eventData)
    {
        $collection = FarmingCollection::where('contract_address', $eventData['contractAddress'])->first();

        $itemPointsDaily = round($collection->farm_points_weekly / 7, 3);

        $contractAddress = strtolower($eventData['contractAddress']);
        $fromAddress = strtolower($eventData['from']);
        $toAddress = strtolower($eventData['to']);

        $loser = DB::table('farming_n_f_t_s')
            ->where('contract_address', $contractAddress)
            ->where('holder', $fromAddress)
            ->first();

        if (!$loser) {
            DB::table('farming_n_f_t_s')->insert([
                'contract_address' => $contractAddress,
                'holder' => $fromAddress,
                'token_balance' => 0,
                'token_balance_last_update' => now(),
                'item_points_daily' => $itemPointsDaily,
                'farm_points_daily_total' => 0,
            ]);

            $loser = DB::table('farming_n_f_t_s')
                ->where('contract_address', $contractAddress)
                ->where('holder', $fromAddress)
                ->first();

        }

        DB::table('farming_n_f_t_s')
            ->where('id', $loser->id)
            ->decrement('token_balance', 1);

        FarmingNFTUpdated::dispatch($loser->id, null, null);

//        $accountExists = DB::table('accounts')
//            ->where('wallet', $loser->holder)
//            ->exists();
//
//        if ($loser->token_balance == 0 || $loser->token_balance < 0) {
//            Log::info('entered here');
//            if($accountExists){
//                DB::table('farming_n_f_t_s')
//                    ->where('id', $loser->id)
//                    ->update([
//                        'token_balance_last_update' => now(),
//                        'item_points_daily' => $itemPointsDaily,
//                        'farm_points_daily_total' => 0,
//                    ]);
//            }else{
//                Log::info('need to delete');
//                Log::debug(json_encode($loser));
//                DB::table('farming_n_f_t_s')
//                    ->where('id', $loser->id)
//                    ->delete();
//            }
//
//            Log::debug(json_encode($loser));
//            if ($loser && $loser->id) {
//                FarmingNFTUpdated::dispatch($loser->id);
//            }
//
//
//        } else {
//            DB::table('farming_n_f_t_s')
//                ->where('id', $loser->id)
//                ->update([
//                    'token_balance_last_update' => now(),
//                    'item_points_daily' => $itemPointsDaily,
//                    'farm_points_daily_total' => round($loser->token_balance * $itemPointsDaily, 3),
//                ]);
//
//            Log::debug(json_encode($loser));
//            if ($loser && $loser->id) {
//                FarmingNFTUpdated::dispatch($loser->id);
//            }
//        }



        // Handle winner updates
        $winner = DB::table('farming_n_f_t_s')
            ->where('contract_address', $contractAddress)
            ->where('holder', $toAddress)
            ->first();


        if (!$winner) {
           $winnerId = DB::table('farming_n_f_t_s')->insertGetId([
                'contract_address' => $contractAddress,
                'holder' => $toAddress,
                'token_balance' => 1,
                'token_balance_last_update' => now(),
                'item_points_daily' => $itemPointsDaily,
                'farm_points_daily_total' => $itemPointsDaily,
            ]);
            FarmingNFTUpdated::dispatch($winnerId, null, null);

//            Log::debug(json_encode($winner));
//            if($winner){
////            $farmingNFT->refresh();
//                FarmingNFTUpdated::dispatch($winner);
//            }
        } else {
            DB::table('farming_n_f_t_s')
                ->where('id', $winner->id)
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

//            Log::debug(json_encode($winner));
//            if($winner && $winner->id){
////            $farmingNFT->refresh();
//
//            }
        }



        $this->info('Successfully updated the app endpoint with transfer event data.');
    }
}
