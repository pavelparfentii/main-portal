<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramReferralKickNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $telegram_id;
    protected $message;

    public function __construct($telegram_id, $message)
    {
        $this->telegram_id = $telegram_id;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (env('APP_ENV') === 'production') {
            $url = "https://t.me/Souls_Club_bot/SCLUB";
        } else {
            $url = "https://t.me/breinburg_bot/test_soul";

        }

        try {
            $telegram = new Api(env('TELEGRAM_BOT'));
//                Log::info('Telegram API initialized.');

            $response = $telegram->sendMessage([
                'chat_id' => $this->telegram_id,
                'text' => $this->message,
                'reply_markup' => Keyboard::make([
                    'inline_keyboard' => [
                        [
                            Keyboard::inlineButton([
                                'text' => '🎮 Launch app',
                                'url' => $url
                            ])
                        ]
                    ]
                ])
            ]);


            $messageId = $response->getMessageId();
            usleep(25000);


        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
        }
    }
}
