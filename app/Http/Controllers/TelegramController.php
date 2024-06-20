<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Telegram;
use App\Models\Week;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

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
//            $id = 367531909;
//
//            Cache::put('Bearer ' . $hash . '.' . $id, 'Bearer ' . $hash . '.' . $id, now()->addMinutes(30));
//
//            return response()->json(['message' => 'Session initiated', 'key' => 'Bearer ' . $hash . '.' . $id]);
            $array = ['367531909', '1235'];

            if(in_array($id, $array)){
                $telegram = Telegram::where('telegram_id', $id)->first();
                $sub = $telegram->account->auth_id;

                $customClaims = [
                    'sub' => $sub,
                    'telegram_id' => $id,
                    'exp' => now()->addYear()->timestamp, // Expiration time (1 hour in the future)
                ];

                //Working with the newest release
                JWTFactory::customClaims($customClaims);
                $payload = JWTFactory::make($customClaims);
                $token = JWTAuth::encode($payload);

                return response()->json(['message' => 'Session initiated', 'token'=>(string)$token, 'exp'=>now()->addYear()->timestamp]);
            }else{
                return response()->json(['errors' => 'Not found'], 404);
            }

        }

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
                'next_update_at' => now()->addMinutes(8),
            ]);

            Cache::forget($cacheKey);

            return response()->json($telegram);
        } else {

            if($telegram->next_update_at < now()){
                $telegram->increment('points', $points);
                $telegram->update(['next_update_at' => now()->addMinutes(8)]);

                Cache::forget($cacheKey);

                return response()->json($telegram);
            } else {
                return response()->json(['message' => 'not allowed to update'], 423);
            }
        }
    }

    public function getPoints2(Request $request)
    {
//        $bearer = $request->bearerToken();
//
//        if (!$bearer) {
//            return response()->json(['errors' => 'No authentification token'], 401);
//        }
//
//        $parts = explode('.', $bearer);
//        if (count($parts) === 2) {
//            $id = $parts[1];
//        }
//        $id = '1234';

        $account = AuthHelper::auth($request);

        if (!$account) {
            return response()->json(['message' => 'non authorized'], 401);
        }

        dd($account);


        $cacheKey = 'telegram_' . $id;

        $response = Cache::get($cacheKey);
        if($response){
            return $response;
        }

        $telegram = Telegram::where('telegram_id', $id)->first();

        if ($telegram ) {
            $account = $telegram->account;
            if($account){
                $response = $this->getInfo($account, $telegram->next_update_at);

                Cache::put($cacheKey, $response, now()->addHours(3));
            }else{
                $account = new Account();
                $account->save();
                $telegram->account()->save($account);
                $response = $this->getInfo($account, $telegram->next_update_at);

                Cache::put($cacheKey, $response, now()->addHours(3));
            }
            return $response;

        } else {
//            $account = new Account();
//            $account->save();
//
//            $telegram = Telegram::create([
//                'telegram_id' => $id,
//                'points' => 0,
//                'next_update_at' => now(),
//            ]);
//            $telegram->account()->save($account);
//
//            $response = $this->getInfo($account);
//            Cache::put($cacheKey, $response, now()->addHours(3));
            return response()->json(['message' => 'not found'], 204);

//            $telegramRecord = DB::table('telegrams')
//                ->table('telegrams')
//                ->where('telegram_id', $id)
//                ->first();
//
////            $telegram = Telegram::on('pgsql_telegrams')->find($telegramRecord->id);
//
//            $telegram = Telegram::where('telegram_id', $id)->first();
//
//            if ($telegram) {
//
//                Cache::put($cacheKey, $telegram, now()->addHours(3));
//
//                return response()->json($telegram);
//            } else {
//                return response()->json(['message' => 'not found'], 204);
//            }
        }
    }

    public function updatePoints2(Request $request)
    {

        $account = AuthHelper::auth($request);

        if (!$account) {
            return response()->json(['message' => 'non authorized'], 401);
        }

        $points = 1.0;
//
//        $bearer = $request->bearerToken();
//
//        if (!$bearer) {
//            return response()->json(['errors' => 'No authentification token'], 401);
//        }

//        $parts = explode('.', $bearer);
//        if (count($parts) === 2) {
//            $id = $parts[1];
//        }
//        $id = '1234';
//        $cacheKey = 'telegram_' . $id;

        $telegram = Telegram::where('account_id', $account->id)->first();


        if(!$telegram){

            return response()->json([

                'user' => null,
                'global'=>[
                    'dropInfo'=>[
                        'nextDrop'=>Carbon::now()->endOfWeek(),
                        'lastDrop'=>Carbon::now()->subWeek()->endOfWeek()

                    ],
                    'total_users'=> DB::table('accounts')->count(),
                    'total_teams'=>DB::table('teams')->count(),
                    'friends'=>!empty($account->twitter_username) ? $account->friends->count() : null,
                    'claimed'=>null,

                ]

            ]);

        }else{

            if ($telegram && $telegram->next_update_at < now()) {
                $account = Account::where('id', $telegram->account_id)->first();

                $currentWeek = Week::getCurrentWeekForAccount($account);
                $currentWeek->increment('points', $points);
                $currentWeek->increment('total_points', $points);

                DB::table('account_farms')
                    ->where('account_id', $account->id)
                    ->increment('total_points', $points);


                $telegram->increment('points', $points);
                $telegram->update(['next_update_at' => now()->addMinutes(8)]);

                $response = $this->getInfo($account, $telegram->next_update_at );

            } else {
                return response()->json(['message' => 'not allowed to update'], 423);
            }

            return $response;
        }

    }

    private function getInfo($account, $next_update_at)
    {

        Week::getCurrentWeekForAccount($account);
        $userRank = DB::table('accounts')
                ->where('total_points', '>', $account->total_points)
                ->count() + 1;

        $account->setAttribute('rank', $userRank);
        $account->setAttribute('current_user', true);
        $account->setAttribute('telegram_next_update_at', $next_update_at);
//            $account->setAttribute('invited', '-');

        if(!empty($account->discord_id)){
            $account->load('discordRoles');
        }

        $accountResourceArray = (new AccountResource($account))->resolve();


        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

//            dd($account->id);
        $currentUserWeekPoints = $account->weeks()
                ->where('week_number', $previousWeekNumber)
                ->where('active', false)
                ->where('claimed', true)
                ->first()
                ->claimed_points ?? null;

        $inviteCheck = DB::table('invites')

            ->where('whom_invited', $account->id)
            ->pluck('id')
            ->toArray();

        $inviteController = new InviteController();
        $inviteCode = $inviteController->activateCode($account);

        $isBlocked = $account->blocked_until;
//            dd($currentUserWeekPoints);

        // if($account->isNeedShow){
        $accountResourceArray['claimed_points']= $currentUserWeekPoints ?? $account->weeks()
                ->where('week_number', $previousWeekNumber)
                ->where('active', false)
                ->where('claimed', true)
                ->first()
                ->claimed_points ?? null;

        $accountResourceArray['invites_count'] = $account->invitesSent()->count() ?? 0;
        $accountResourceArray['invited'] = !empty($inviteCheck) ? true : false;
        $accountResourceArray['invite_code']=$inviteCode;
        $accountResourceArray['isBlocked'] = !is_null($isBlocked) ? true : false;
        $accountResourceArray['total_points']=$account->dailyFarm->total_points;
        $accountResourceArray['daily_farm']=$account->dailyFarm->daily_farm;

        $account->telegram()->exists() ? $accountResourceArray['telegram_next_update'] = $account->telegram->next_update_at : $accountResourceArray['telegram_next_update'] = null;
        // }

        $claimed = $account->weeks()
            ->where('week_number', $previousWeekNumber)
            ->where('active', false)
            ->where('claimed', false)->first();

        return response()->json([

                'user' => $accountResourceArray,
                'global'=>[
                    'dropInfo'=>[
                        'nextDrop'=>Carbon::now()->endOfWeek(),
                        'lastDrop'=>Carbon::now()->subWeek()->endOfWeek()

                    ],
                    'total_users'=> DB::table('accounts')->count(),
                    'total_teams'=>DB::table('teams')->count(),
                    'friends'=>!empty($account->twitter_username) ? $account->friends->count() : null,
                    'claimed'=>$claimed ? false : true,

                ]

        ]);
    }

    public static function generateToken(Request $request): string
    {
        // Example payload (claims)
        $customClaims = [
            'sub' => $request->sub,
            'telegram_id' => $request->wallet_address,
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
