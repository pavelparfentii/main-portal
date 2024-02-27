<?php

namespace App\Helpers;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthHelper
{
    public static function auth(Request $request)
    {

        $token = $request->bearerToken();

//        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJlNDMwMDliNi0yYWYzLTQ4NDQtYTk5NC1lZWZiOWY4ZTgwOGUiLCJleHAiOjE3MDI2NTc2ODEsIm5hbWUiOm51bGwsImVtYWlsIjoicGF2ZWxwcmZudEBnbWFpbC5jb20iLCJ3YWxsZXRfYWRkcmVzcyI6IjB4MjliOTIxN2Q1YjA0NWYwNEY1OEY2NDFFOEVkQmQ4YkVmY2M2MTQ5YyIsImlzX2FkbWluIjp0cnVlLCJkaXNjb3JkIjpudWxsLCJ0d2l0dGVyIjp7InByb3ZpZGVyX2lkIjoiMTQ5NjgyMDM5NjUwMjQzMzc5NyIsInVzZXJfbmFtZSI6IlBQYXJmZW50aWkiLCJuYW1lIjoicGhwIGFydGlzYW4iLCJwcm9maWxlX2ltYWdlX3VybCI6Imh0dHBzOi8vcGJzLnR3aW1nLmNvbS9wcm9maWxlX2ltYWdlcy8xNDk2ODIwNDUzMTc1OTY3NzQ5L2lQZ2tmazV6X2JpZ2dlci5wbmcifX0.TZ4ju3PJY3U9ifCPL22T5DCnOxQu6Grb_4fbFzWv8BU';


        try {

            $decodedToken = JWTAuth::setToken($token)->getPayload();

            $authId = !is_null($decodedToken['sub']) ? $decodedToken['sub'] : null;

            $userWallet = strtolower($decodedToken['wallet_address']);
//            $tokenTwitterName = !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['name'] : null;
//            $tokenTwitterAvatar = !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['profile_image_url'] : null;
//            $discordUserName = !is_null($decodedToken['discord']) ? $decodedToken['discord']['user_name'] : null;
//            $discordId = !is_null($decodedToken['discord']) ? $decodedToken['discord']['provider_id'] : null;


            if (!is_null($userWallet)) {
                $account = Account::where('wallet', $userWallet)->first();

                if (!$account) {
                    // User doesn't exist, create a new user
                    $account = Account::create([
                        'wallet' => $userWallet,
                        'auth_id' => $decodedToken['sub'],
                        'twitter_id' => !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['provider_id'] : null,
                        'twitter_username' => !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['user_name'] : null,
//                        'twitter_name' => !is_null($decodedToken['twitter']) ? $decodedToken['twitter']['name'] : null,
//                        'twitter_avatar' => !is_null($decodedToken['twitter']) ? $account->downloadTwitterAvatar($decodedToken['twitter']['profile_image_url']) : null,
                        'discord_id' => !is_null($decodedToken['discord']) ? $decodedToken['discord']['provider_id'] : null,
//                        'discord_name' => !is_null($decodedToken['discord']) ? $decodedToken['discord']['user_name'] : null

                    ]);

                }
                return $account;
            }

        } catch (JWTException $exception) {
//            throw new InvalidTokenException('Invalid or expired token');
            Log::info('jwt exception: ' . $exception);
//            return response()->json(['error' => 'token expired or wrong'], 403);
            return false;
        }

//        $tokenParts = explode(".", $token);
//        $tokenHeader = base64_decode($tokenParts[0]);
//        $tokenPayload = base64_decode($tokenParts[1]);
//        $jwtHeader = json_decode($tokenHeader);
//        $jwtPayload = json_decode($tokenPayload);
//        $timestamp = 1701204002;
//        $dateTime = Carbon::createFromTimestamp($timestamp);

//        return $decodedToken;

    }
}
