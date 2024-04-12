<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\SafeSoul;
use App\Traits\SafeSoulTrait;
use App\Traits\TwitterTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemporaryController extends Controller
{
    use TwitterTrait;
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

    public function testEndp()
    {
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJiODdiNWY5Yi1kNDA0LTQxOWItOGRiYS0wZjIxZDE3Mjk5ZWMiLCJleHAiOjE3MTI5MTgyNjYsIm5hbWUiOm51bGwsImVtYWlsIjpudWxsLCJ3YWxsZXRfYWRkcmVzcyI6IjB4MjliOTIxN2Q1YjA0NWYwNEY1OEY2NDFFOEVkQmQ4YkVmY2M2MTQ5YyIsImlzX2FkbWluIjp0cnVlLCJkaXNjb3JkIjp7InByb3ZpZGVyX2lkIjoiOTgzODAwNDk4NTY4MTk2MDk3IiwidXNlcl9uYW1lIjoiIn0sInR3aXR0ZXIiOnsicHJvdmlkZXJfaWQiOiIxNDk2ODIwMzk2NTAyNDMzNzk3IiwidXNlcl9uYW1lIjoicGhwX2FydGlzYWgiLCJuYW1lIjoicGhwIGFydGlzYW4iLCJwcm9maWxlX2ltYWdlX3VybCI6Imh0dHBzOi8vcGJzLnR3aW1nLmNvbS9wcm9maWxlX2ltYWdlcy8xNDk2ODIwNDUzMTc1OTY3NzQ5L2lQZ2tmazV6LnBuZyJ9fQ.XB3B8tgu1ZSCZYmTv0LHQNA3NFCnGRP2v7-WPAGdljA'
        ])->withBody(json_encode([
            "861fd7e5-dc34-42e3-9632-63a501569c09", "89b8f0ac-9b0d-422c-8d20-07d34c803eac"
        ]), 'application/json')->get('https://stg-auth-app-tyqys.ondigitalocean.app/management/users/');

        dd($response->body());
        $response->body();
    }

}
