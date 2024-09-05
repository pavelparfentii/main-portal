<?php

namespace App\Jobs;

use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class DownloadTelegramAvatar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tgAccount;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($tgAccount)
    {
        $this->tgAccount = $tgAccount;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $currentDate = Carbon::now();
        try {
            $telegram = new Api(env('TELEGRAM_BOT'));

            $response = $telegram->getUserProfilePhotos(['user_id' => $this->tgAccount->telegram_id]);

            $photos_count = $response->getTotalCount();

            if ($photos_count > 0) {
                $photos = $response->getPhotos();
                $first_photo = $photos[0][0] ?? null;

                if ($first_photo && isset($first_photo['file_id'])) {
                    $file_id = $first_photo['file_id'];
                    $response = $telegram->getFile(['file_id' => $file_id]);

                    if ($response && isset($response['file_path']) && is_string($response['file_path'])) {
                        $path = $response['file_path'];
                        $token = env('TELEGRAM_BOT');
                        $url = "https://api.telegram.org/file/bot$token/$path";

                        $photo = $this->downloadTelegramAvatar($url);
                        $this->tgAccount->update(['avatar' => $photo, 'avatar_downloaded_at'=>$currentDate]);
                        usleep(25000);
                    }
                }
            }
        } catch (TelegramSDKException $e) {
            Log::info('Error: ' . $e->getMessage());
        }
    }

    private function downloadTelegramAvatar($url): ?string
    {
        $Telegram_AVATAR_PATH = 'telegram/avatars';

        try {
            if ($url) {
                $contents = file_get_contents($url);

                $filename = $Telegram_AVATAR_PATH . '/' . md5($url) . '.' . pathinfo($url, PATHINFO_EXTENSION);

                Storage::disk('public')->put($filename, $contents);

                return url('storage/' . $filename);
            }
        } catch (Exception $exception) {
            Log::error('Error while loading telegram avatar: ' . $exception->getMessage());
            return null;
        }

        return null;
    }
}
