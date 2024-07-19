<?php

namespace App\Console\Commands;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\Telegram;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
    public function handle()
    {
        $batchSize = 100; // Adjust based on your performance testing
        $now = Carbon::now();

        $day1 = 1;
        $day2 = 2;
        $day3 = 3;
        $day4 = 4;

        Telegram::on('pgsql_telegrams')
            ->where('notification_sent', false)

            ->where('next_update_at', '<=', $now->subDays(3))
            ->where('notification_sent', false)
            ->groupBy('telegram_id', 'telegrams.id')
            ->chunkById($batchSize, function ($users) use ($now, $day1, $day2, $day3, $day4) {
                foreach ($users as $user) {


                    $lastInteraction = Carbon::parse($user->next_update_at);
                    var_dump($lastInteraction);

                    $message = null;
//                    var_dump($user->telegram_id);

                    if($now->diffInHours($lastInteraction) >= $day1 && $now->diffInHours($lastInteraction) < $day2) {
                        $message = "Hey hey, it's Diamonds calling! Come back to tap!";
                    }elseif ($now->diffInHours($lastInteraction) >= $day2 && $now->diffInHours($lastInteraction) < $day3){
                        $message = "Diamonds are losing their shine without you. Tap to keep them sparkling!";
                    }elseif ($now->diffInHours($lastInteraction) >= $day3 && $now->diffInHours($lastInteraction) < $day4){
                        $message = "Your gems miss you. Tap to reunite with your treasures!";
                    }elseif($now->diffInHours($lastInteraction) >= $day4){
                        $message = "It's been a while! Your diamonds are still here, waiting for you. Come back and tap to gather them!";
                    }

                    if ($message) {
                        $this->sendMessage($user, $message);

                    }
                }
            });
    }

    private function sendMessage($user, $message)
    {
        $delaySeconds = 3;
        sleep($delaySeconds);
        var_dump($user->telegram_id);
        SendTelegramNotificationJob::dispatch($user, $message)
            ->delay(now()->addSeconds($delaySeconds))
            ->onQueue('telegram');
    }

}
