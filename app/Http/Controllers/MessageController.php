<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Week;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class MessageController extends Controller
{
    public function message(Request $request):void
    {

        $data = $request->all(); // Convert request data to array if it's not already
        $authId = $data['auth_id']; // Extract the 'auth_id'

        unset($data['auth_id']); // Remove 'auth_id' from the data to be updated

        if (array_key_exists('user', $data)) {
            $checkAccount = Account::where('auth_id', $authId)->first();
            if(!$checkAccount){
                $account = new Account();
                $account->auth_id = $authId;
                $account->wallet = $data['wallet'];
                $account->email = $data['email'];

                $account->save();
                Week::getCurrentWeekForAccount($account);
            }

        }else{
            if (!empty($data['twitter_avatar'])) {
                $downloadedAvatarUrl = $this->downloadTwitterAvatar($data['twitter_avatar']);
                if ($downloadedAvatarUrl !== null) {

                    $data['twitter_avatar'] = $downloadedAvatarUrl;
                } else {

                    unset($data['twitter_avatar']);
                }
            }

            DB::table('accounts')->where('auth_id', $authId)->update($data);
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
            return null;
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
