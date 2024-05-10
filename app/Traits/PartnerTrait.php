<?php

namespace App\Traits;

use App\Models\Account;
use App\Models\MoralisAPI;
use App\Models\PartnerNFT;
use App\Models\SafeSoul;
use App\Models\Week;
use Illuminate\Database\Eloquent\Model;

trait PartnerTrait
{

    public function checkTokensOwnership()
    {


        $partners = PartnerNFT::cursor();
        foreach ($partners as $partner) {
            $contract_address = strtolower($partner->contract_address);
            $token_id = $partner->token_id;
            $owners = $this->moralisTokenRequest($contract_address, $token_id);

            if (count($owners) > 0) {
                foreach ($owners as $owner) {
                    $account = Account::where('wallet', $owner)->first();
                    if ($account) {
                        $currentWeek = Week::getCurrentWeekForAccount($account);

                        $partnerToken = SafeSoul::where('query_param', 'partner_token_' . $partner->token_id)->first();

                        if (!$partnerToken) {
                            var_dump('hew');
                            $newReward = new SafeSoul([
                                'account_id' => $account->id,
                                'points' => $partner->reward_points,
                                'comment' => $partner->collection_name . ' token owner' . $partner->token_id,
                                'query_param' => 'partner_token_' . $partner->token_id
                            ]);
                            $newReward->save();
                            $currentWeek->animals()->save($newReward);
                            $currentWeek->increment('points', $partner->reward_points);
                            $currentWeek->increment('total_points', $partner->reward_points);
                        }
                    }
                }
            }

        }
    }

    private function moralisTokenRequest($contract_address, $token_id): array
    {
        $client = new \GuzzleHttp\Client();
        $next_cursor = null;

        $owners = [];

        do {
            try {
                $url = "https://deep-index.moralis.io/api/v2.2/nft/{$contract_address}/{$token_id}/owners?chain=eth&format=decimal";
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
                            $owners[] = strtolower($item['owner_of']);
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
}
