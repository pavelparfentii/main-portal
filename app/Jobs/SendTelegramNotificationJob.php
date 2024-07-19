<?php

namespace App\Jobs;


use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Traits\Telegram;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $user;
    protected $message;

    public function __construct($user, $message)
    {
        $this->user = $user;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::info('Starting handle method.');

        Log::info('User telegram_id: ' . $this->user->telegram_id);
        $connection = $this->user->getConnectionName();
        Log::info('Connection: ' . $connection);

        if ($connection === 'pgsql_telegrams') {
            Log::info('Connection is pgsql_telegrams.');

            if (env('APP_ENV') === 'production') {
                $url = "https://tg-app.souls.club";
            } else {
                $url = "https://t.me/breinburg_bot/test_soul";
//                $url = "https://tg-app.souls.club";
            }
            Log::info('URL: ' . $url);

            try {
                $telegram = new Api(env('TELEGRAM_BOT'));
                Log::info('Telegram API initialized.');

                $response = $telegram->sendMessage([
                    'chat_id' => $this->user->telegram_id,
                    'text' => $this->message,
                    'reply_markup' => Keyboard::make([
                        'inline_keyboard' => [
                            [
                                Keyboard::inlineButton([
                                    'text' => 'Open Mini App',
                                    'url' => $url
                                ])
                            ]
                        ]
                    ])
                ]);
                Log::info('Message sent.');

                $this->user->notification_sent = true;
                $this->user->last_notification_at = Carbon::now();
                $this->user->save();
//                Log::info('User notification status updated.');

                $messageId = $response->getMessageId();
//                Log::info('Message ID: ' . $messageId);

            } catch (\Exception $e) {
                Log::error('Error sending message: ' . $e->getMessage());
            }
        } else {
            Log::info('Connection is not pgsql_telegrams.');
        }

//        Log::info('Starting handle method.');
//
//        Log::info('User telegram_id: ' . $this->user->telegram_id);
//        $connection = $this->user->getConnectionName();
//        Log::info('Connection: ' . $connection);
//
//        if ($connection === 'pgsql_telegrams') {
//            Log::info('Connection is pgsql_telegrams.');
//
//            if (env('APP_ENV') === 'production') {
//                $url = "https://tg-app.souls.club";
//            } else {
//                $url = "https://tg-app.souls.club";
//            }
//            Log::info('URL: ' . $url);
//
//            try {
//                $client = new Client();
//                $response = $client->post('https://api.telegram.org/bot' . env('TELEGRAM_BOT') . '/sendMessage', [
//                    'form_params' => [
//                        'chat_id' => $this->user->telegram_id,
//                        'text' => $this->message,
//                        'reply_markup' => json_encode([
//                            'inline_keyboard' => [
//                                [
//                                    [
//                                        'text' => 'Open Mini App',
//                                        'url' => $url
//                                    ]
//                                ]
//                            ]
//                        ])
//                    ]
//                ]);
//
//                $responseBody = json_decode($response->getBody(), true);
//
//                if ($responseBody['ok']) {
//                    Log::info('Message sent.');
//
//                    $this->user->notification_sent = true;
//                    $this->user->save();
//                    Log::info('User notification status updated.');
//
//                    $messageId = $responseBody['result']['message_id'];
//                    Log::info('Message ID: ' . $messageId);
//                } else {
//                    Log::error('Error sending message: ' . $responseBody['description']);
//                }
//
//            } catch (\Exception $e) {
//                Log::error('Error sending message: ' . $e->getMessage());
//            }
//        } else {
//            Log::info('Connection is not pgsql_telegrams.');
//        }

    }
}
