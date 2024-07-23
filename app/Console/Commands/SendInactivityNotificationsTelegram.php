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

    public function handle()
    {
        $batchSize = 100; // Adjust based on your performance testing
        $timezone = config('app.timezone');
        $now = Carbon::now($timezone);

        $days1 = 1;
        $days2 = 3;
        $days3 = 10;
        $days4 = 20;

        Telegram::on('pgsql_telegrams')

            ->where('next_update_at', '<=', $now->subHours($days1)->toDateTimeString())
            ->groupBy('telegram_id')
            ->chunkById($batchSize, function ($telegrams) use ($now, $days1, $days2, $days3, $days4) {
                foreach ($telegrams as $telegram) {


                    $lastNotificationSentAt = $telegram->last_notification_at ? Carbon::parse($telegram->last_notification_at) : null;
                    $nextUpdateAt = $telegram->next_update_at ? Carbon::parse($telegram->next_update_at) : null;

                    $createdAt = $telegram->created_at ? Carbon::parse($telegram->created_at) : null;


                    if((!$nextUpdateAt && $now->diffInHours($createdAt) > $days1) || ($lastNotificationSentAt && $nextUpdateAt->gt($lastNotificationSentAt))){

                        $telegram->update([
                            'notification_stage' => 0,
                            'last_notification_at' => null
                        ]);
                        continue;
                    }

                    //юзер новий next_update_at дата создания  > 1 дня то notification_stage проходит так само

                    $message = null;

                    switch ($telegram->notification_stage) {
                        case 0:
                            if ($now->diffInHours($nextUpdateAt) >= $days1) {
                                $message = "Hey hey, it's Diamonds calling! Come back to tap!";
                                $telegram->notification_stage = 1;
                            }
                            break;

                        case 1:
                            if ($now->diffInDays($nextUpdateAt) >= $days2) {
                                $message = "Diamonds are losing their shine without you. Tap to keep them sparkling!";
                                $telegram->notification_stage = 2;
                            }
                            break;

                        case 2:
                            if ($now->diffInDays($nextUpdateAt) >= $days3) {
                                $message = "Your gems miss you. Tap to reunite with your treasures!";
                                $telegram->notification_stage = 3;
                            }
                            break;

                        case 3:
                            if ($now->diffInDays($nextUpdateAt) >= $days4) {
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

        SendTelegramNotificationJob::dispatch($user, $message)
            ->delay(now()->addSeconds($delaySeconds))
            ->onQueue('telegram');
    }

}
