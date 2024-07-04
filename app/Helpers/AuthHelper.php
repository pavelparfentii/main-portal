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

                if(!is_null($userWallet) && !empty($userWallet)){
                    $account = Account::where('wallet', $userWallet)->orWhere('auth_id', $authId)->first();
                }else{
                    $account = Account::where('auth_id', $authId)->first();
                }



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
                        'email'=>$decodedToken['email'] ?? null,
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
                    $account->update(['email' => $email]);
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
        return false;
    }

}
