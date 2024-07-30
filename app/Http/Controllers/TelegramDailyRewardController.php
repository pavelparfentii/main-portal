<?php

namespace App\Http\Controllers;

use App\Events\TelegramPointsUpdated;
use App\Helpers\AuthHelperTelegram;
use App\Models\Account;
use App\Models\DailyReward;
use App\Models\Telegram;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class TelegramDailyRewardController extends Controller
{
    public function getDailyReward(Request $request)
    {

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


        //для прода
        // $loginTime = Carbon::instance($authDateTime);
        $loginTime = Carbon::now();

        $dateNow = Carbon::now();
        $currentCarbon = Carbon::instance($currentDateTime);

        //ПРОД
        // if (!$loginTime->isSameDay($currentCarbon)) {
        //     return response()->json(['status' => 'error', 'message' => 'Timestamp is too old'], 400);
        // }

        //----------------------------------------------------

        $account = AuthHelperTelegram::auth($request);

        $dailyReward = DailyReward::on('pgsql_telegrams')->firstOrCreate(
            ['account_id' => $account->id],
            ['previous_login' => $loginTime, 'days_chain' => 0, 'update_reward_at'=>$dateNow->subHours(25)]
        );


        $previousLogin = Carbon::parse($dailyReward->previous_login);

        $daysChain = $dailyReward->days_chain;

        $rewardUpdatedAt = Carbon::parse($dailyReward->update_reward_at);

        $hoursDifference = $previousLogin->diffInHours($loginTime);
        //$sameDay = $previousLogin->isSameDay($loginTime);
        $result = null;

        if($hoursDifference >= 24){
            $daysChain = 1;

            $result =$this->updateReward($daysChain, $account, $dailyReward);

        }

        if($hoursDifference < 24){


            if ( $loginTime->diffInHours($rewardUpdatedAt) > 24) {
                $daysChain += 1;

                $result = $this->updateReward($daysChain, $account, $dailyReward);

            }

        }

        $dailyReward->previous_login = $loginTime;
        $dailyReward->save();

        if($result){
            return response()->json($result, 200);
        }

        return response('', 200);

    }

    private function updateReward($daysChain, $account, $dailyReward)
    {
        $timezone = config('app.timezone');
        $currentDateTime = Carbon::now($timezone);


        $dailyReward->update([
            'days_chain' => $daysChain,
            'update_reward_at'=>$currentDateTime
        ]);


        $coef = ($daysChain >= 30) ? 30 : $daysChain;
        $pointsToAdd = $coef * 0.1;

        $this->updateTapTime($account, $daysChain);

        $additionalReward = 0;

        $cacheKey = 'bonus_reward_'.$account->id;
        if($daysChain % 4 == 0 ){

            $additionalReward = $this->getPoints();
            $response = $additionalReward;

            Cache::put($cacheKey, $response, now()->addHours(24));

        }

        $totalPoints = $pointsToAdd;

        $account->increment('total_points', $totalPoints);

        $this->updateForReferrals($account, $totalPoints);

        return [
            'days_chain'=>$daysChain,
            'bonus_reward'=>$additionalReward,
            'crystal'=> 0.1,
            'timer'=> 2,
        ];

    }

    private function updateTapTime($account, $daysChain)
    {
        $timezone = config('app.timezone');
        $now = Carbon::now($timezone);

        if ($account->telegram()->exists()) {

            if($daysChain <= 1){
                $timeDiscount = 0;
            }elseif($daysChain >1 && $daysChain <=9){
                $timeDiscount = $daysChain-1;
            }else{
                $timeDiscount = 8;
            }

            $tapTime = Carbon::parse($account->telegram->next_update_at);

            if($tapTime > $now ){
                $newTapTime = $tapTime->subHours($timeDiscount * 2);

                $account->telegram->next_update_at = $tapTime->diffInHours($newTapTime) < 0 ? $now : $newTapTime;
                $account->telegram->save();

            }
        }
    }

    private function getPoints()
    {
        $values = [1, 11, 69, 111];
        $frequencies = [89, 9.5, 1, 0.5];

        // Создание и заполнение префиксного массива
        $prefix = [];
        $prefix[0] = $frequencies[0];
        for ($i = 1; $i < count($frequencies); ++$i) {
            $prefix[$i] = $prefix[$i - 1] + $frequencies[$i];
        }

        // Префиксный массив: [89, 98.5, 99.5, 100]
        // Генерация случайного числа от 1 до суммы всех вероятностей
        $random = mt_rand(1, 100 * 1000) / 1000;

        // Поиск индекса случайного числа в префиксном массиве
        $index = $this->findRandomInPrefixArray($prefix, $random, 0, count($prefix) - 1);
        return $values[$index];
    }

    private function updateForReferrals($account, $points)
    {
        $exists = DB::connection('pgsql_telegrams')
            ->table('account_referrals')
            ->where('ref_subref_id', $account->id)
            ->exists();

        if ($exists) {
            DB::connection('pgsql_telegrams')
                ->table('account_referrals')
                ->where('ref_subref_id', $account->id)
                ->increment('income', $points);
        }
    }


    private function findRandomInPrefixArray($prefix, $random, $start, $end) {
        while ($start < $end) {
            $mid = intdiv($start + $end, 2);
            if ($random > $prefix[$mid]) {
                $start = $mid + 1;
            } else {
                $end = $mid;
            }
        }

        return ($prefix[$start] >= $random) ? $start : -1;
    }

    public function claimBonusReward(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        $cacheKey = 'bonus_reward_'.$account->id;

        if (Cache::has($cacheKey)) {
            $account->increment('total_points', (int)Cache::get($cacheKey));

            $this->updateForReferrals($account, (int)Cache::get($cacheKey));

            Cache::forget($cacheKey);

            return response()->json(['message' => 'success'], 200);
        }else{
            return response()->json(['error' => 'no bonus points to claim'], 403);
        }

    }


}
