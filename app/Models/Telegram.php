<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\DailyReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class Telegram extends Model
{
    use HasFactory;

    protected $guarded = false;


    protected $hidden = ['updated_at', 'created_at'];

    protected $dates = ['last_notification_at'];

    protected $casts = [
        'next_update_at' => 'datetime',
        'notification_sent' => 'boolean',
    ];

    protected $appends = ['next_update_with_reward_at'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    protected static function booted()
    {
        static::retrieved(function ($telegram) {

        });

        static::created(function ($telegram) {
            $connection = $telegram->getConnectionName();

            if ($connection === 'pgsql_telegrams') {
                // Перевіряємо умови, які повинні бути виконані для оновлення
                if (!$telegram->getOriginal('avatar')) {
                    // Виконуємо оновлення лише якщо avatar ще не було встановлено
                    $telegram->update(['avatar' => $telegram->getAvatar()]);
                }
            }
        });

        static::updated(function ($telegram) {
            $connection = $telegram->getConnectionName();

            if ($connection === 'pgsql_telegrams') {

                if ($telegram->points > 0 && $telegram->wasChanged('points')) {
                    // Виконуємо оновлення лише якщо змінено поле 'points'
                    $telegram->update(['avatar' => $telegram->getAvatar()]);
                }
            }
        });
    }

    public function calculate($timeDiscount, $now)
    {
        if ($timeDiscount === null || $now === null) {
            return $this->attributes['next_update_at'];
        }

        $tapTime = Carbon::parse($this->attributes['next_update_at']);
        $newTapTime = $tapTime->subHours($timeDiscount * 2);

        return $tapTime->diffInHours($newTapTime) < 0 ? $now : $newTapTime;

    }

//    public function setNextUpdateAtAttribute($value)
//    {
//        $this->attributes['next_update_at'] = $value;
//    }

    public function getNextUpdateWithRewardAtAttribute($timeDiscount = null, $now = null)
    {
        $connection = $this->getConnectionName();

        if ($connection === 'pgsql_telegrams') {

            $account = $this->account;

            if ($account) {
                $accountId = $account->id;

                $dailyReward = DailyReward::on('pgsql_telegrams')->where('account_id', $accountId)->first();

                if (!$dailyReward) {
                    return;
                }

                $daysChain = $dailyReward->days_chain;

                if ($daysChain <= 1) {
                    $timeDiscount = 0;
                } elseif ($daysChain > 1 && $daysChain <= 9) {
                    $timeDiscount = $daysChain - 1;
                } else {
                    $timeDiscount = 8;
                }

                $now = Carbon::now(); // Додаємо поточний час


                return $this->calculate($timeDiscount, $now);
            }
        }

    }

    public function getAvatar()
    {
        try {
            $telegram = new Api(env('TELEGRAM_BOT'));

            $response = $telegram->getUserProfilePhotos(['user_id' => '188893613']);

            $photos_count = $response->getTotalCount();

            if($photos_count > 0){
                $photos = $response->getPhotos();

                $first_photo = $photos[0][0] ?? null;

                // Get the file_id of the first photo
                $file_id = $first_photo['file_id'];

                // You can now use $file_id to download or perform further actions
//            dd($file_id);
                if(is_string($file_id)){
                    $response = $telegram->getFile(['file_id' => "$file_id"]);
                    if ($response && isset($response['file_path']) && is_string($response['file_path'])) {
                        $path = $response['file_path'];

                        if(isset($path) && is_string($path)){
//                            dd($path);
                            $token = env('TELEGRAM_BOT');
                            $url = "https://api.telegram.org/file/bot$token/$path";
//                            dd($url);
                            $this->downloadTelegramAvatar($url);

                        }
                    }
                }
            }
        }catch (TelegramSDKException $e){
            Log::info('Error: ' . $e->getMessage());
        }


    }

    public function downloadTelegramAvatar($result): ?string
    {
        $Telegram_AVATAR_PATH = 'telegram/avatars';

        try {

            if (isset($result)) {

                $url = $result;

                $contents = file_get_contents($url);

                $filename = $Telegram_AVATAR_PATH. '/'. md5($url) . '.' . pathinfo($url, PATHINFO_EXTENSION);

                Storage::disk('public')->put($filename, $contents);

                $fullUrl = url('storage/' .$filename);

                return $fullUrl;
            }

        } catch (Exception $exception) {
            Log::error('Error while loading twitter avatar: ' . $exception->getMessage());
            return null;
        }

        return null;
    }
}
