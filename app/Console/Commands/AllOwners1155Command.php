<?php

namespace App\Console\Commands;

use App\Models\FarmingCollection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AllOwners1155Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:all-owners1155-command';

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
        $alchemyApi = 'i9Zy6n-JRr6S90IiG0H24u-Br2k-b6T8';

        $contractAddress = '0x76be3b62873462d2142405439777e971754e8e77';
//        $contractAddress = '0x60e4d786628fea6478f785a6d7e704777c86a7c6';
        //0xc36cf0cfcb5d905b8b513860db0cfe63f6cf9f5c;
        $client = new \GuzzleHttp\Client();
        $next_cursor = null;

        $collection = FarmingCollection::where('contract_address', $contractAddress)->first();

        $itemPointsDaily = round($collection->farm_points_weekly / 7, 3);


        do{
            try {
                $url = "https://eth-mainnet.g.alchemy.com/nft/v3/$alchemyApi/getOwnersForContract?contractAddress=$contractAddress&withTokenBalances=true";
//                sleep(5);
                if ($next_cursor !== null) {
//                    sleep(5);
                    $url .= "&pageKey=$next_cursor";
                }

                $response = $client->request('GET', $url , [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                ]);

                $responseBody = json_decode($response->getBody(), true);

                if (isset($responseBody['owners'])) {
                    $owners = $responseBody['owners'];
                    foreach ($owners as $owner){
                        if(isset($owner['ownerAddress'])){
                            $wallet = strtolower($owner['ownerAddress']);
                            if(isset($owner['tokenBalances'])){
                                $tokenBalances = $owner['tokenBalances'];

                                foreach ($tokenBalances as $token){
                                    $collectionToken = DB::table('farming_collections')
                                        ->where('token_id', $token['tokenId'])
                                        ->first();

                                    // If the token_id exists in the farming_collections table, proceed
                                    if ($collectionToken) {
                                        $winner = DB::table('farming_n_f_t_s')
                                            ->where('contract_address', $contractAddress)
                                            ->where('holder', $wallet)
                                            ->where('token_id', $token['tokenId'])
                                            ->first();

                                        if (!$winner) {
                                            DB::table('farming_n_f_t_s')->insert([
                                                'contract_address' => $contractAddress,
                                                'holder' => $wallet,
                                                'token_balance' => (int)$token['balance'],
                                                'token_id' => $token['tokenId'],
                                                'token_balance_last_update' => now(),
                                                'item_points_daily' => $itemPointsDaily,
                                                'farm_points_daily_total' => (int)$token['balance'] * $itemPointsDaily,
                                            ]);
                                        }
                                    }
                                }
                            }

                        }
                    }
                }


                $next_cursor = isset($responseBody['pageKey']) && ($responseBody['pageKey'] !== '0') ? $responseBody['pageKey'] : null;
            }catch (\GuzzleHttp\Exception\GuzzleException $e){
                $this->error("An error occurred while fetching data: " . $e->getMessage());
                // Optionally, break or continue depending on how you want to handle failures
                break;
            }

        }while($next_cursor);






        $this->info('success');
    }
}
