<?php

namespace App\Http\Controllers;

use App\ConstantValues;
use App\Events\TelegramPointsUpdated;
use App\Helpers\AuthHelper;
use App\Helpers\AuthHelperTelegram;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\DigitalAnimal;
use App\Models\Invite;
use App\Models\Telegram;
use App\Models\Week;
use App\Traits\TelegramTrait;
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
    use TelegramTrait;
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


        if (true) {
//            отправить на аус сервис запрос с user_id, чего получим токен, + решреш , передавать в респонсе токен + рефреш
            $id = json_decode($queryParams['user'])->id;

            $telegram = Telegram::where('telegram_id', $id)->first();

            if(!$telegram){
                $auth_id = $this->generateUuidV4();
                $account = Account::create([
                   'auth_id'=> $auth_id,
                   'total_points'=>0
                ]);
                $telegram = Telegram::create(
                    [
                      'account_id'=>$account->id,
                      'telegram_id'=>$id,
                      'next_update_at'=>now(),
                    ]
                );

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
                $telegram = Telegram::on('pgsql_telegrams')->where('telegram_id', $id)->first();
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


    public function updatePoints2(Request $request)
    {
//        $cacheKey = 'telegram_' . $id;
        $account = AuthHelperTelegram::auth($request);

        if (!$account) {
            return response()->json(['message' => 'non authorized'], 401);
        }

        $points = 1.0;

        $telegram = Telegram::on('pgsql_telegrams')
            ->where('account_id', $account->id)
            ->first();


        if(!$telegram){

            return response()->json([

                'user' => null,
                'global'=>[
                    'dropInfo'=>[
                        'nextDrop'=>Carbon::now()->endOfWeek(),
                        'lastDrop'=>Carbon::now()->subWeek()->endOfWeek()

                    ],
                    'total_users'=> DB::connection('pgsql_telegrams')->table('accounts')->count(),
                    'total_teams'=>DB::connection('pgsql_telegrams')->table('teams')->count(),
                    'friends'=>!empty($account->twitter_username) ? $account->friends->count() : null,
                    'claimed'=>null,

                ]

            ]);

        }else{

            if ($telegram && $telegram->next_update_at < now()) {
                $account = Account::on('pgsql_telegrams')
                    ->where('id', $telegram->account_id)
                    ->first();


                //for current total
                $account->increment('total_points', $points);
                $account->save();
//                DB::connection('pgsql_telegrams')
//                    ->table('account_farms')
//                    ->where('account_id', $telegram->account_id)
//                    ->increment('total_points', $points);

                DB::connection('pgsql_telegrams')
                    ->table('account_farms')
                    ->where('account_id', $telegram->account_id)
                    ->increment('daily_farm', $points);


                $telegram->increment('points', $points);
                $telegram->update(['next_update_at' => now()->addHours(24)]);

                $response = $this->getInfo($request);

            } else {
                return response()->json(['message' => 'not allowed to update'], 423);
            }

            return $response;
        }

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

    function generateUuidV4() {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // set version to 0100
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    //SEPARATE DATABASE TELEGRAMS_DB
    //-----------------------------------------------------------------------------------------------------------------

    public function initiateSessionSeparateDB(Request $request)
    {
//            dd('here');
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

        $jsonString = $queryParams['user'] ?? null;

        $user = json_decode($jsonString, true);

        $fist_name = $user['first_name'] ?? null;
        $last_name = $user['last_name'] ?? null;
        $username = $user['username'] ?? null;
        $avatar = null;

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

//        if(true){
          if ($computedHash == $hash) {
            //отправить на аус сервис запрос с user_id, чего получим токен, + решреш , передавать в респонсе токен + рефреш
            $id = json_decode($queryParams['user'])->id;
//            $id = '888';
            $telegram = Telegram::on('pgsql_telegrams')
                ->where('telegram_id', $id)
                ->first();

            if(!$telegram){
                $auth_id = $this->generateUuidV4();


                $account = Account::on('pgsql_telegrams')->create([
                    'auth_id'=> $auth_id,
                    'total_points'=>0
                ]);


                $telegram = Telegram::on('pgsql_telegrams')->create(
                    [
                        'account_id'=>$account->id,
                        'first_name'=>$fist_name,
                        'last_name'=>$last_name,
                        'telegram_username'=>$username,
                        'avatar'=>$avatar,
                        'telegram_id'=>$id,
                        'next_update_at'=>now(),
                    ]
                );

                event(new TelegramPointsUpdated($account));

                $sub = $telegram->account->auth_id;

                $customClaims = [
                    'sub' => $sub,
                    'telegram_id' => $id,
                    'is_new'=>true,
                    'exp' => now()->addYear()->timestamp, // Expiration time (1 hour in the future)
                ];

                //Working with the newest release
                JWTFactory::customClaims($customClaims);
                $payload = JWTFactory::make($customClaims);
                $token = JWTAuth::encode($payload);

                return response()->json(['message' => 'Session initiated', 'token'=>(string)$token, 'exp'=>now()->addYear()->timestamp]);

            }else{
                $telegram = Telegram::on('pgsql_telegrams')->where('telegram_id', $id)->first();

                if(empty($telegram->first_name) || is_null($telegram->first_name)){
                    $telegram->update([
                        'first_name'=>$fist_name,
                        'last_name'=>$last_name,
                        'telegram_username'=>$username,
                        'avatar'=>$avatar,
                    ]);
                }

                event(new TelegramPointsUpdated($telegram->account));

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

                return response()->json([
                    'message' => 'Session initiated',
                    'token'=>(string)$token,
                    'exp'=>now()->addYear()->timestamp]);
            }

        }

        return response()->json(['errors' => 'Authentification failed'], 401);
    }

    public function getInfoEndpoint(Request $request)
    {
        return $this->getInfo($request);
    }

    public function getPointsDataEndpoint(Request $request)
    {
        return $this->getData($request);
    }

    public function getFriendsForAccountEndpoint(Request $request)
    {
        return $this->getFriendsForAccount($request);
    }

    public function makeTeamEndpoint(Request $request)
    {
        return $this->makeTeam($request);
    }

    public function joinTeamEndpoint(Request $request)
    {
        return $this->joinTeam($request);
    }

    public function getTeamListEndpoint(Request $request)
    {
        return $this->getTeamList($request);
    }

    public function getTeamsListEndpoint(Request $request)
    {
        return $this->getTeamsList($request);
    }

    public function leaveTeamEndpoint(Request $request)
    {
        return $this->leaveTeam($request);
    }

    public function checkNameEndpoint(Request $request)
    {
        return $this->checkName($request);
    }

    public function inviteUserEndpoint(Request $request)
    {
        return $this->inviteUser($request);
    }

    public function getReferralsDataEndpoint(Request $request)
    {
        return $this->getReferralsData($request);
    }

    public function claimIncomeEndpoint(Request $request)
    {
        return $this->claimIncome($request);
    }



}
