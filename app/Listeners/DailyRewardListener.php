<?php

namespace App\Listeners;

use App\Events\DailyRewardEvent;
use App\Models\DailyReward;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class DailyRewardListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DailyRewardEvent $event): void
    {
        $account = $event->account;
        $loginTime = $event->loginTime;

        $dailyReward = DailyReward::firstOrCreate(
            ['account_id' => $account->id],
            ['previous_login' => $loginTime, 'days_chain' => 0]
        );


        if($dailyReward){

            $previousLogin = $dailyReward->previous_login ? Carbon::parse($dailyReward->previous_login) : null;
            $daysChain = $dailyReward->days_chain;
            $hoursDifference = $previousLogin->diffInHours($loginTime) ?? null;
            //$sameDay = $previousLogin->isSameDay($loginTime);

            if ($hoursDifference >= 24 && $hoursDifference < 48) {
                // Увеличиваем цепочку дней на 1
                $daysChain += 1;

                $this->updateReward($daysChain, $account, $previousLogin, $loginTime);


            } elseif ($hoursDifference >= 48) {
                // Сбрасываем цепочку дней
                $daysChain = 1;

                $this->updateReward($daysChain, $account, $previousLogin, $loginTime);

            } else {
            // Первый вход, устанавливаем цепочку дней в 1
                $daysChain = 1;


                $this->updateReward($daysChain, $account, $previousLogin, $loginTime);
            }

        }
    }

    private function updateReward($daysChain, $account, $dailyReward, $loginTime)
    {
        $timezone = config('app.timezone');
        $currentDateTime = Carbon::now($timezone);

        $previousLogin = $dailyReward->previous_login ? Carbon::parse($dailyReward->previous_login) : null;


        if ($previousLogin && $previousLogin->diffInHours($currentDateTime) < 24) {
            return; // Выходим из функции, если еще не прошли 24 часа
        }

        $dailyReward->update([
            'previous_login' => $loginTime,
            'days_chain' => $daysChain
        ]);


        $coef = ($daysChain >= 30) ? 30 : $daysChain;
        $pointsToAdd = ($daysChain >= 30) ? 3 : $coef * 0.1;

        $account->increment('total_points', $pointsToAdd);
        $this->updateTapTime($account, $daysChain);

    }

    private function updateTapTime($account, $daysChain)
    {
        $timezone = config('app.timezone');
        $now = Carbon::now($timezone);

        if ($account->telegram()->exists()) {

            if($daysChain <= 0){
                $timeDiscount = 0;
            }elseif($daysChain >1 && $daysChain <=9){
                $timeDiscount = $daysChain -1;
            }else{
                $timeDiscount = 8;
            }

           $tapTime = Carbon::parse($account->telegram->next_update_at);

           if($tapTime > $now && $tapTime->diffInHours($now) >= $timeDiscount * 2 ){
               $newTapTime = $tapTime->subHours($timeDiscount);


               $account->telegram->next_update_at = $newTapTime;
               $account->telegram->save();
           }
        }
    }
}
