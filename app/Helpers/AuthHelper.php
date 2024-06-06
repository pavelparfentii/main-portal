<?php

namespace App\Helpers;

use App\Exceptions\InvalidTokenException;
use App\Http\Controllers\InviteController;
use App\Models\Account;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthHelper
{
//    public static function auth(Request $request)
//    {
//
//        $token = $request->bearerToken();
//
////        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJlNDMwMDliNi0yYWYzLTQ4NDQtYTk5NC1lZWZiOWY4ZTgwOGUiLCJleHAiOjE3MDk3NDA3NTksIm5hbWUiOiJQYXNoYSIsImVtYWlsIjoicGF2ZWxwcmZudEBnbWFpbC5jb20iLCJ3YWxsZXRfYWRkcmVzcyI6IjB4MjliOTIxN2Q1YjA0NWYwNEY1OEY2NDFFOEVkQmQ4YkVmY2M2MTQ5YyIsImlzX2FkbWluIjp0cnVlLCJkaXNjb3JkIjp7InByb3ZpZGVyX2lkIjoiOTgzODAwNDk4NTY4MTk2MDk3IiwidXNlcl9uYW1lIjoiYnJlaW42ODA0In0sInR3aXR0ZXIiOnsicHJvdmlkZXJfaWQiOiIxNDk2ODIwMzk2NTAyNDMzNzk3IiwidXNlcl9uYW1lIjoicGhwX2FydGlzYWgiLCJuYW1lIjoicGhwIGFydGlzYW4iLCJwcm9maWxlX2ltYWdlX3VybCI6Imh0dHA6Ly9hcGkuc2FmZXNvdWwuY2x1Yi9zdG9yYWdlL3R3aXR0ZXIvYXZhdGFycy82NTY1Yzk4NmM4YjE3NzY3NjE0NjM0NmIxN2UzMDVhNy5wbmcifX0.JzKYfHS_vFw5Qakt3ULEwroHYFEGrMTXBClY7gVHwRc';
//
//        try {
//
//            $decodedToken = JWTAuth::setToken($token)->getPayload();
//
//            $authId = !is_null($decodedToken['sub']) ? $decodedToken['sub'] : null;
//
//            $userWallet = !is_null($decodedToken['wallet_address']) ? strtolower($decodedToken['wallet_address']) : null;
//            $tokenTwitterName = !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['name'] : null;
//            $tokenTwitterUsername =!is_null($decodedToken['twitter']) ? strtolower($decodedToken['twitter']['user_name']) : null;
////            $tokenTwitterAvatar = !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['profile_image_url'] : null;
//            $discordUserName = !is_null($decodedToken['discord']) ? $decodedToken['discord']['user_name'] : null;
//            $discordId = !is_null($decodedToken['discord']) ? $decodedToken['discord']['provider_id'] : null;
//
//            $cacheKey = 'wallet_check:' . $userWallet;
//            $isHotWallet = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userWallet) {
//
//                return self::checkHotWallet($userWallet);
//            });
//
//
//            if ($isHotWallet === false) {
//                if (!is_null($userWallet) || !is_null($tokenTwitterUsername) || !is_null($discordId) || isset($authId)) {
//                    $account = Account::where('auth_id', $authId)
//                        ->first();
//
//                    if (!$account) {
//                        // User doesn't exist, create a new user
//                        $account = Account::create([
//                            'wallet' => $userWallet,
//
//                            'twitter_id' => !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['provider_id'] : null,
//                            'twitter_username' => !is_null($decodedToken['twitter']) ? strtolower($decodedToken['twitter']['user_name']) : null,
//                            'twitter_name' => !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['name'] : null,
//                            //'twitter_avatar' => !is_null($decodedToken['twitter']) ? $account->downloadTwitterAvatar($decodedToken['twitter']['profile_image_url']) : null,
//                            'twitter_avatar'=>$decodedToken['twitter']['profile_image_url'] ?? null,
//                            'discord_id' => !is_null($decodedToken['discord']) ? $decodedToken['discord']['provider_id'] : null,
//                            'auth_id'=>$authId,
//                            'isNeedShow' => false,
////                        'discord_name' => !is_null($decodedToken['discord']) ? $decodedToken['discord']['user_name'] : null
//
//                        ]);
//
//                        if (!is_null($decodedToken['twitter'])) {
//                            $avatarUrl = $decodedToken['twitter']['profile_image_url'];
//                            $avatar = $account->downloadTwitterAvatar($avatarUrl);
//                            $account->update(['twitter_avatar' => $avatar]);
//
//                        }
//
//                        Week::getCurrentWeekForAccount($account);
//                    }
//
//                    $account->update(['wallet'=>$userWallet]);
//
//                    return $account;
//                }
//            }else{
//                $originalWallet = strtolower($isHotWallet);
//
//                $account = Account::where('wallet', $originalWallet)->first();
//
//                if (!$account) {
//                    // User doesn't exist, create a new user
//                    $account = Account::create([
//                        'wallet' => $userWallet,
//
//                        'twitter_id' => !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['provider_id'] : null,
//                        'twitter_username' => !is_null($decodedToken['twitter']) ? strtolower($decodedToken['twitter']['user_name']) : null,
//                        'twitter_name' => !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['name'] : null,
//                        //'twitter_avatar' => !is_null($decodedToken['twitter']) ? $account->downloadTwitterAvatar($decodedToken['twitter']['profile_image_url']) : null,
//                        'twitter_avatar'=>$decodedToken['twitter']['profile_image_url'] ?? null,
//                        'discord_id' => !is_null($decodedToken['discord']) ? $decodedToken['discord']['provider_id'] : null,
//                        'auth_id'=>$authId,
//                        'isNeedShow' => false,
////                        'discord_name' => !is_null($decodedToken['discord']) ? $decodedToken['discord']['user_name'] : null
//
//                    ]);
//
//                    if (!is_null($decodedToken['twitter'])) {
//                        $avatarUrl = $decodedToken['twitter']['profile_image_url'];
//                        $avatar = $account->downloadTwitterAvatar($avatarUrl);
//                        $account->update(['twitter_avatar' => $avatar]);
//
//                    }
//
//                    Week::getCurrentWeekForAccount($account);
//                }
//
//                return $account;
//            }
//
//
//
//        } catch (JWTException $exception) {
////            throw new InvalidTokenException('Invalid or expired token');
////            Log::info('jwt exception: ' . $exception);
//
//            return false;
////            throw new HttpException(403, 'Token expired or incorrect');
//        }
//
//    }

    public static function auth(Request $request)
    {
        $token = $request->bearerToken();
        try {
            $decodedToken = JWTAuth::setToken($token)->getPayload();
            $authId = $decodedToken['sub'] ?? null;
            $userWallet = strtolower($decodedToken['wallet_address'] ?? '');
            $cacheKey = 'wallet_check:' . $userWallet;

            return DB::transaction(function () use ($decodedToken, $userWallet, $authId, $cacheKey) {
                $isHotWallet = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userWallet) {
                    return self::checkHotWallet($userWallet);
                });

                if ($isHotWallet !== false) {
                    $userWallet = strtolower($isHotWallet);  // Assume checkHotWallet returns the correct wallet address
                }

                $account = Account::where('wallet', $userWallet)->orWhere('auth_id', $authId)->first();

                if (!$account) {
                    // User doesn't exist, create a new user
                    $account = Account::create([
                        'wallet' => $userWallet,

                        'twitter_id' => !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['provider_id'] : null,
                        'twitter_username' => !is_null($decodedToken['twitter']) ? strtolower($decodedToken['twitter']['user_name']) : null,
                        'twitter_name' => !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['name'] : null,
                        //'twitter_avatar' => !is_null($decodedToken['twitter']) ? $account->downloadTwitterAvatar($decodedToken['twitter']['profile_image_url']) : null,
                        'twitter_avatar'=>$decodedToken['twitter']['profile_image_url'] ?? null,
                        'discord_id' => !is_null($decodedToken['discord']) ? $decodedToken['discord']['provider_id'] : null,
                        'auth_id'=>$authId,
                        'isNeedShow' => false,
                        'email'=>$decodedToken['email'] ?? null
//                        'discord_name' => !is_null($decodedToken['discord']) ? $decodedToken['discord']['user_name'] : null

                    ]);

                    if (!is_null($decodedToken['twitter'])) {
                        $avatarUrl = $decodedToken['twitter']['profile_image_url'];
                        $avatar = $account->downloadTwitterAvatar($avatarUrl);
                        $account->update(['twitter_avatar' => $avatar]);

                    }


                    Week::getCurrentWeekForAccount($account);
                }
                if(!is_null($decodedToken['email'])){
                    $email = $decodedToken['email'] ?? null;
                    $account->update(['twitter_avatar' => $email]);
                }

                $account->update(['wallet'=>$userWallet]);

                return $account;
            });
        } catch (JWTException $exception) {
            Log::error('JWT Exception: ' . $exception->getMessage());
            return false;  // or handle as per your application's requirement
        }
    }

    protected static function checkHotWallet($wallet)
    {
        $process = new Process([
            'node',
            base_path('/node/delegateCash.js'),
            $wallet
        ]);

        $process->run();
        $process->setTimeout(2);
        if($process->isSuccessful()){

            $result = json_decode($process->getOutput());


            if (!$result || $result->state != 'success') {
                Log::info('node process delegatecash broke');
                return false;
            } else {

                if(isset($result->data) && !empty($result->data)){

                    return strtolower($result->data);
                }
                return false;
            }
        }
    }

}
