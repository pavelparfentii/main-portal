<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class MessageController extends Controller
{
    public function message(Request $request):void
    {
        $payload = $request->json()->all();

        $decodedArray = $payload[0];

        $decodedFinally = json_decode($decodedArray, true);

        $workPayload = $decodedFinally['payload'];
        $auth_id = $workPayload['data']['user_uuid'] ?? null;

    //        Cache::pull($auth_id);

        if(!empty($workPayload) && !empty($workPayload['source']) && $workPayload['source']=== 'twitter'){
            $action = $workPayload['action'] ?? null;
            $twitterData = $workPayload['data'] ?? null;

            $twitterId = $twitterData['id']?? null;
            $twitterName = $twitterData['name']?? null;
            $twitterUsername = $twitterData['user_name']?? null;
            $twitterImage =$twitterData['profile_image_url'] ?? null;

            $avatarUrl = $this->downloadTwitterAvatar($twitterImage);

            if(!empty($action) && !empty($auth_id) && $action === 'create'){
                DB::table('accounts')->where('auth_id', $auth_id)->update([
                    'twitter_id'=>$twitterId,
                    'twitter_name'=>$twitterName,
                    'twitter_username'=>$twitterUsername,
                    'twitter_avatar'=>$avatarUrl
                ]);
            }
            if(!empty($action) && !empty($auth_id) && $action === 'delete'){
                DB::table('accounts')->where('auth_id', $auth_id)->update([
                    'twitter_id'=>null,
                    'twitter_name'=>null,
                    'twitter_username'=>null,
                    'twitter_avatar'=>null
                ]);
            }
        }

        if(!empty($workPayload) && !empty($workPayload['source']) && $workPayload['source']=== 'discord'){
            $action = $workPayload['action'] ?? null;
            $discordData = $workPayload['data'] ?? null;

            $discordId = $discordData['id']?? null;
            $discordUsername = $discordData['user_name'] ?? null;

            if(!empty($action) && !empty($auth_id) && $action === 'create'){
                DB::table('accounts')->where('auth_id', $auth_id)->update([
                    'discord_id'=>$discordId,


                ]);
            }
            if(!empty($action) && !empty($auth_id) && $action === 'delete'){
                DB::table('accounts')->where('auth_id', $auth_id)->update([
                    'discord_id'=>null,

                ]);
            }
        }
    }

    public function downloadTwitterAvatar($result): ?string
    {
        $TWITTER_AVATAR_PATH = 'twitter/avatars';

        try {

            if (isset($result)) {

                $url = $result;

                $contents = file_get_contents($url);

                $filename = $TWITTER_AVATAR_PATH. '/'. md5($url) . '.' . pathinfo($url, PATHINFO_EXTENSION);

                Storage::disk('public')->put($filename, $contents);

                $fullUrl = url('storage/' .$filename);
                return $fullUrl;
            }

        } catch (Exception $exception) {
            Log::error('Error while loading twitter avatar: ' . $exception->getMessage());
        }

        return null;
    }

    public static function generateToken(Request $request): string
    {
        // Example payload (claims)
        $customClaims = [
            'sub' => $request->sub,
            'wallet_address' => $request->wallet_address,
            'exp' => now()->addYear()->timestamp, // Expiration time (1 hour in the future)
        ];

        //Working with the newest release
        JWTFactory::customClaims($customClaims);
        $payload = JWTFactory::make($customClaims);
        $token = JWTAuth::encode($payload);

        return response()->json(['token'=>(string)$token, 'exp'=>now()->addYear()->timestamp]);
//        return (string)$token;
    }
}
