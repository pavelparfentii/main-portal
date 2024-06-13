<?php

namespace App\Http\Controllers;

use App\Models\Telegram;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TelegramController extends Controller
{
    public function initiateSession(Request $request)
    {

//        $botToken = '7107960895:AAGGcZrH0Vein6bWR-_IgG2xVxZU6foBQ3U';

        $botToken = env('TELEGRAM_BOT');
        $WebAppData = $request->get('WebAppInitData');

        $validator = Validator::make($request->all(), [
            'WebAppInitData' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        parse_str($WebAppData, $queryParams);

        $hash = '';

        $dataCheckString = '';
        ksort($queryParams);
        foreach ($queryParams as $key => $value) {
            if ($key !== 'hash') {

                $dataCheckString .= "$key=$value\n";
            } else {
                $hash = $value;
            }
        }

        $dataCheckString = rtrim($dataCheckString, "\n");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);

        $computedHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        $authDate = $queryParams['auth_date'] ?? null;

        $authDateTime = new DateTime("@$authDate");
        $authDateTime->setTimezone(new DateTimeZone('Europe/Moscow')); // Assuming the auth_date is in UTC
        $currentDateTime = new DateTime('now', new DateTimeZone('Europe/Moscow'));
        $interval = $currentDateTime->diff($authDateTime);

        // Convert the interval to seconds

        $intervalInSeconds = ($interval->days * 24 * 60 * 60) +
            ($interval->h * 60 * 60) +
            ($interval->i * 60) +
            $interval->s;

        $environment = App::environment(['local', 'staging']);
        if (!$environment && $intervalInSeconds > 60) {
            return response()->json(['status' => 'error', 'message' => 'Timestamp is too old'], 403);
        }


        if ($computedHash == $hash) {
            //отправить на аус сервис запрос с user_id, чего получим токен, + решреш , передавать в респонсе токен + рефреш
            $id = json_decode($queryParams['user'])->id;

            Cache::put('Bearer ' . $hash . '.' . $id, 'Bearer ' . $hash . '.' . $id, now()->addMinutes(30));

            return response()->json(['message' => 'Session initiated', 'key' => 'Bearer ' . $hash . '.' . $id]);
        };

        return response()->json(['errors' => 'Authentification failed'], 401);


    }

    public function getPoints(Request $request)
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return response()->json(['errors' => 'No authentification token'], 401);
        }

        $parts = explode('.', $bearer);
        if (count($parts) === 2) {
            $id = $parts[1];
        }

        $cacheKey = 'telegram_' . $id;

        $telegram = Cache::get($cacheKey);

        if ($telegram) {
            return response()->json($telegram);
        } else {
            $telegram = Telegram::where('telegram_id', $id)->first();

            if ($telegram) {

                Cache::put($cacheKey, $telegram, now()->addHours(3));

                return response()->json($telegram);
            } else {
                return response()->json(['message' => 'not found'], 204);
            }
        }
    }

    public function updatePoints(Request $request)
    {

        $points = 1.0;

        $bearer = $request->bearerToken();

        if (!$bearer) {
            return response()->json(['errors' => 'No authentification token'], 401);
        }

        $parts = explode('.', $bearer);
        if (count($parts) === 2) {
            $id = $parts[1];
        }
        $cacheKey = 'telegram_' . $id;


        $telegram = Telegram::where('telegram_id', $id)->first();

        if (!$telegram) {

            $telegram = Telegram::create([
                'telegram_id' => $id,
                'points' => $points,
                'next_update_at' => now()->addHours(8),
            ]);

            Cache::forget($cacheKey);

            return response()->json($telegram);
        } else {

            if($telegram->next_update_at < now()){
                $telegram->increment('points', $points);
                $telegram->update(['next_update_at' => now()->addHours(8)]);

                Cache::forget($cacheKey);

                return response()->json($telegram);
            } else {
                return response()->json(['message' => 'not allowed to update'], 423);
            }
        }
    }
}
