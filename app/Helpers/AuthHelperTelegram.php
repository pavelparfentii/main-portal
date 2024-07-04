<?php


namespace App\Helpers;


use App\Models\Account;
use App\Models\Week;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthHelperTelegram
{
    public static function auth(Request $request)
    {
        $token = $request->bearerToken();
        try {
            $decodedToken = JWTAuth::setToken($token)->getPayload();
            $authId = $decodedToken['sub'] ?? null;

            $account = Account::on('pgsql_telegrams')->where('auth_id', $authId)->first();

            if(!$account){
                $account = Account::on('pgsql_telegrams')->create([
                    'auth_id'=> $authId,
                    'total_points'=>0
                ]);
            }


            Week::getCurrentWeekForTelegramAccount($account);

            return $account;


            }catch (JWTException $exception){
            Log::error('JWT Exception: ' . $exception->getMessage());
            return false;
        }
    }
}
