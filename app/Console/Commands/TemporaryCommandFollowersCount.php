<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class TemporaryCommandFollowersCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    private $count;

    protected $signature = 'app:temporary-command-followers-count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function __construct()
    {
        parent::__construct();
        $this->count = 1;
    }

    public function handle()
    {

        $csv = array_map('str_getcsv', file('storage/app/public/accounts_202404301338.csv'));

// Initialize an empty array to store the data
        $array_da = [];

// Loop through each row of the CSV array
        foreach ($csv as $row) {
            // Skip the header row
            if ($row[0] === 'wallet' && $row[1] === 'twitter_id') {
                continue;
            }

            // Assign wallet address as key and Twitter ID as value in the data array
            $array_da[$row[0]] = $row[1];
        }

        $accounts = Account::whereNotNull('twitter_id')->whereNotNull('wallet')->get();
        foreach ($accounts as $account){
            if(!in_array($account->wallet, $array_da)){
                $array_da[$account->wallet]= $account->twitter_id;
            }
        }

        $array_da_final = [];

        $bar = $this->output->createProgressBar(count($array_da));

        $bar->start();


        foreach ($array_da as $wallet=>$twitter) {
            $process = new Process([
                'node',
                base_path('node/getAnimalsId.js'),
                $wallet,
            ]);

            $process->run();

            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput());
                if (is_array($data) && (count($data) > 0)) {
                    $this->info(count($data));
                    $array_da_final[$wallet]=$twitter;
                }
            }
            $bar->advance();

        }
        $bar->finish();

        Log::info($array_da_final);


        $myArray = array_values($array_da_final);

        $batchSize = 100; // Number of elements in each batch

        $totalItems = count($myArray); // Total number of items in the array
        $totalBatches = ceil($totalItems / $batchSize); // Total number of batches needed

// Loop through batches

//        $url ='';

        for ($i = 0; $i < $totalBatches; $i++) {
            // Get a slice of the array for the current batch
            $batch = array_slice($myArray, $i * $batchSize, $batchSize);
            $url = 'https://twitter288.p.rapidapi.com/user/details/bulk?ids=';
            // Process the batch
            foreach ($batch as $item) {

                $url .=$item.',';
//                $url .= $item . ',';
            }


            $client = new \GuzzleHttp\Client();

            $response = $client->request('GET', $url, [
                'headers' => [
                    'X-RapidAPI-Host' => 'twitter288.p.rapidapi.com',
                    'X-RapidAPI-Key' => 'be57165779msh42e81c435e412e1p1a5e97jsn890a5a58f40f',
                ],
            ]);
            $responseBody = json_decode($response->getBody(), true);
            foreach ($responseBody as $entry){

                if(isset($entry['normal_followers_count'])){
                    var_dump('here');
                    $this->count += $entry['normal_followers_count'];
                    var_dump($this->count);
                    Log::info($this->count);
                }
            }
            sleep(10);

        }


    }
}
