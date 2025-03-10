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
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;

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

    public function testEndp(Request $request)
    {

        try {
            $telegram = new Api(env('TELEGRAM_BOT'));

            $response = $telegram->getUserProfilePhotos(['user_id' => '188893613']);

            $photos_count = $response->getTotalCount();

            if($photos_count > 0){
                $photos = $response->getPhotos();

                $first_photo = $photos[0][0] ?? null;

                // Get the file_id of the first photo
                $file_id = $first_photo['file_id'];

                // You can now use $file_id to download or perform further actions
//            dd($file_id);
                if(is_string($file_id)){
                    $response = $telegram->getFile(['file_id' => "$file_id"]);
                    if ($response && isset($response['file_path']) && is_string($response['file_path'])) {
                        $path = $response['file_path'];

                        if(isset($path) && is_string($path)){
//                            dd($path);
                            $token = env('TELEGRAM_BOT');
                            $url = "https://api.telegram.org/file/bot$token/$path";
//                            dd($url);
                            $this->downloadTelegramAvatar($url);

                        }
                    }
                }
            }
        }catch (TelegramSDKException $e){
            Log::info('Error: ' . $e->getMessage());
        }


    }

    public function downloadTelegramAvatar($result): ?string
    {
        $TWITTER_AVATAR_PATH = 'telegram/avatars';

        try {

            if (isset($result)) {

                $url = $result;

                $contents = file_get_contents($url);

                $filename = $TWITTER_AVATAR_PATH. '/'. md5($url) . '.' . pathinfo($url, PATHINFO_EXTENSION);

                Storage::disk('public')->put($filename, $contents);

                $fullUrl = url('storage/' .$filename);
                dd($fullUrl);
                return $fullUrl;
            }

        } catch (Exception $exception) {
            Log::error('Error while loading twitter avatar: ' . $exception->getMessage());
            return null;
        }

        return null;
    }

}
