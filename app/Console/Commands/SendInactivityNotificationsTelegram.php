<?php

namespace App\Console\Commands;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\Telegram;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendInactivityNotificationsTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-inactivity-notifications-telegram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
//    public function handle()
//    {
//        $batchSize = 100; // Adjust based on your performance testing
//        $now = Carbon::now();
//
//        $day1 = 1;
//        $day2 = 2;
//        $day3 = 3;
//        $day4 = 4;
//
//        $threeHoursAgo = $now->subHours(1);
//        $fourHoursAgo = $now->subHours(4);
//
//        Telegram::on('pgsql_telegrams')
//            ->where('notification_sent', true)
//            ->where('next_update_at', '<', $fourHoursAgo)
//            ->chunkById($batchSize, function ($telegrams) use ($now, $day1, $day2, $day3, $day4) {
//            foreach ($telegrams as $telegram) {
//                $lastNotification = $telegram->last_notification_at ? Carbon::parse($telegram->last_notification_at) : null;
//
//                if ($lastNotification === null) {
//                    continue;
//                }
//
//                if ($now->diffInHours($lastNotification) >= $day1 && $now->diffInHours($lastNotification) < $day2) {
//                    $telegram->update(['notification_sent' => false]);
//                } elseif ($now->diffInHours($lastNotification) >= $day2 && $now->diffInHours($lastNotification) < $day3) {
//                    $telegram->update(['notification_sent' => false]);
//                } elseif ($now->diffInHours($lastNotification) >= $day3 && $now->diffInHours($lastNotification) < $day4) {
//                    $telegram->update(['notification_sent' => false]);
//                }elseif ($now->diffInHours($lastNotification) > $day4){
//                    $telegram->update(['notification_sent' => true]);
//                }
//            }
//        });
//
//
//        Telegram::on('pgsql_telegrams')
//            ->where('notification_sent', false)
//
//            ->where('next_update_at', '<', $threeHoursAgo)
//            ->where('notification_sent', false)
//            ->groupBy('telegram_id', 'telegrams.id')
//            ->chunkById($batchSize, function ($users) use ($now, $day1, $day2, $day3, $day4) {
//                foreach ($users as $user) {
//
//
//                    $lastInteraction = Carbon::parse($user->next_update_at);
//                    var_dump($lastInteraction);
//
//                    $message = null;
////                    var_dump($user->telegram_id);
//
//                    if($now->diffInHours($lastInteraction) >= $day1 && $now->diffInHours($lastInteraction) < $day2) {
//                        $message = "Hey hey, it's Diamonds calling! Come back to tap!";
//                    }elseif ($now->diffInHours($lastInteraction) >= $day2 && $now->diffInHours($lastInteraction) < $day3){
//                        $message = "Diamonds are losing their shine without you. Tap to keep them sparkling!";
//                    }elseif ($now->diffInHours($lastInteraction) >= $day3 && $now->diffInHours($lastInteraction) < $day4){
//                        $message = "Your gems miss you. Tap to reunite with your treasures!";
//                    }elseif($now->diffInHours($lastInteraction) >= $day4){
//                        $message = "It's been a while! Your diamonds are still here, waiting for you. Come back and tap to gather them!";
//                    }
//
//                    if ($message) {
//                        $this->sendMessage($user, $message);
//
//                    }
//                }
//            });
//    }

    public function handle()
    {
        $batchSize = 100; // Adjust based on your performance testing
        $now = Carbon::now();

        $days1 = 1;
        $days2 = 2;
        $days3 = 3;
        $days4 = 4;

        Telegram::on('pgsql_telegrams')->chunkById($batchSize, function ($telegrams) use ($now, $days1, $days2, $days3, $days4) {
            foreach ($telegrams as $telegram) {
                $lastNotificationSentAt = $telegram->last_notification_sent_at ? Carbon::parse($telegram->last_notification_sent_at) : null;
                $nextUpdateAt = $telegram->next_update_at ? Carbon::parse($telegram->next_update_at) : null;


                if ($nextUpdateAt && $lastNotificationSentAt && $nextUpdateAt->gt($lastNotificationSentAt)) {

                    $telegram->update([
                        'notification_stage' => 0,
                        'last_notification_sent_at' => null
                    ]);
                    continue;
                }

                $message = null;

                switch ($telegram->notification_stage) {
                    case 0:
                        if ($now->diffInHours($nextUpdateAt) >= $days1) {
                            $message = "Hey hey, it's Diamonds calling! Come back to tap!";
                            $telegram->notification_stage = 1;
                        }
                        break;

                    case 1:
                        if ($now->diffInHours($nextUpdateAt) >= $days2) {
                            $message = "Diamonds are losing their shine without you. Tap to keep them sparkling!";
                            $telegram->notification_stage = 2;
                        }
                        break;

                    case 2:
                        if ($now->diffInHours($nextUpdateAt) >= $days3) {
                            $message = "Your gems miss you. Tap to reunite with your treasures!";
                            $telegram->notification_stage = 3;
                        }
                        break;

                    case 3:
                        if ($now->diffInHours($nextUpdateAt) >= $days4) {
                            $message = "It's been a while! Your diamonds are still here, waiting for you. Come back and tap to gather them!";
                            $telegram->notification_stage = 4;
                        }
                        break;

                    default:
                        break;
                }

                if ($message) {
                    DB::transaction(function () use ($telegram, $message, $now) {
                        $this->sendMessage($telegram, $message);
                        $telegram->save();
                    });
                }
            }
        });
    }

    private function sendMessage($user, $message)
    {
        $delaySeconds = 3;
        sleep($delaySeconds);
//        var_dump($user->telegram_id);
        SendTelegramNotificationJob::dispatch($user, $message)
            ->delay(now()->addSeconds($delaySeconds))
            ->onQueue('telegram');
    }

}
