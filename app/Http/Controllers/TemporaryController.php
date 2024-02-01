<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\SafeSoul;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemporaryController extends Controller
{
    public function sample()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiToken,
        ])->get('https://provider-app/api/data');

        $client = new Client();
        $response = $client->get('your-api-endpoint', [
            'headers' => [
                'Authorization' => 'Bearer YOUR_API_KEY',
                // Add other headers as needed
            ],
        ]);

        //Data Retrieval:
        $response = Http::get('https://source-app.com/api/data');
        $data = $response->json();
    }

    public function dataAccamulation()
    {
//        $response = Http::get('http://127.0.0.1:8000/api/airdrop/invites');
        $response = Http::withHeaders([
            'Authorization' => 'ux9AgzrHauOq6sF8kZSetP4eCAB7z2OoxLOQP1fpQCZflMrPBQdUVdQehCdGDrPZ',
        ])->get('http://127.0.0.1:8000/api/airdrop/invites');

        if($response->status() ==200){

            $data = $response->json();
            if(isset($data)){
                foreach ($data as $item){

                    $account = Account::where('wallet', $item['wallet'])->first();
                    if($account){
                        $safeSoul = new SafeSoul([
                            'points' => $item['points']
                        ]);

                        $account->safeSouls()->save($safeSoul);

                        // Update the total_points field in the Account model
                        $account->total_points += $item['points'];
                        $account->save();
                    }else{
                        $account = new Account([
                           'wallet'=> $item['wallet']
                        ]);
                        $account->save();

                        $safeSoul = new SafeSoul([
                            'points' => $item['points']
                        ]);

                        Log::info($account);

                        // Manually set the relationship
                        $safeSoul->account()->associate($account);
                        $safeSoul->save();

                        // Update the total_points field in the Account model
                        $account->total_points += $item['points'];
                        $account->save();
                    }
                }
            }
            return $response->json(['ok'=>'sdsfd'],200);
        }

    }

}
