<?php

namespace App\Console\Commands;

use App\Models\FarmingCollection;
use App\Models\FarmingNFT;
use App\Models\MoralisAPI;

use Illuminate\Console\Command;

class MoralisHoldersCollectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:moralis-holders-collect-command';

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
//       $collections = FarmingCollection::get();
//
//       foreach ($collections as $collection){
//            $this->moralisTokenRequest($collection->contract_address);
//
//       }
//
//
//
//        foreach ($collections as $collection){
//            $holders = FarmingNFT::where('contract_address', $collection->contract_address)->cursor();
//            foreach ($holders as $holder){
//                $holder->token_balance_last_update = now();
//                $holder->item_points_daily = $collection->farm_points_weekly/7;
//                $holder->farm_points_daily_total = $holder->token_balance + $collection->farm_points_weekly/7;
//                $holder->save();
//            }
//            $this->moralisTokenRequest($collection->contract_address);
//
//        }
//
//        $this->info('success');

        $apes = FarmingNFT::where('contract_address', '0x60e4d786628fea6478f785a6d7e704777c86a7c6')->get();

        $collection = FarmingCollection::where('contract_address', '0x60e4d786628fea6478f785a6d7e704777c86a7c6')->first();
        foreach ($apes as $ape){
            $ape->token_balance_last_update = now();
            $ape->item_points_daily = $collection->farm_points_weekly/7;
            $ape->farm_points_daily_total = $ape->token_balance * $collection->farm_points_weekly/7;
            $ape->save();
        }

        $this->info('success');

    }



    private function moralisTokenRequest($contract_address): array
    {
        $client = new \GuzzleHttp\Client();
        $next_cursor = null;

        $owners = [];

        do {
            try {
                $url = "https://deep-index.moralis.io/api/v2.2/nft/{$contract_address}/owners?chain=eth&format=decimal";
                if ($next_cursor !== null) {
                    sleep(2);
                    $url .= "&cursor=$next_cursor";
                }

                $response = $client->request('GET', $url, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-API-Key' => MoralisAPI::getApiKey(),
                    ],
                ]);

                $responseBody = json_decode($response->getBody(), true);


                if (isset($responseBody['result'])) {
                    foreach ($responseBody['result'] as $item) {
                        if (isset($item['owner_of'])) {

                            $this->updateHolders($contract_address, strtolower($item['owner_of']));
//                            $owners[] = strtolower($item['owner_of']);
                        }
                    }
                }

                $next_cursor = $responseBody['cursor'] ?? null;

                var_dump($next_cursor);

            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                $this->error("An error occurred while fetching data: " . $e->getMessage());
                // Optionally, break or continue depending on how you want to handle failures
                break;
            }
        } while ($next_cursor);

        return $owners;
    }

    private function updateHolders($contract_address, $owner){
        $holder = FarmingNFT::where('contract_address', $contract_address)
            ->where('holder', $owner)->first();


        if(!$holder){

            FarmingNFT::create([
                'contract_address'=> $contract_address,
                'holder'=>$owner
            ]);
        }else{
            $holder->increment('token_balance', 1);

        }

    }
}
