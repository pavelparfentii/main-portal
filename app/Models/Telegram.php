<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\DailyReward;

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
}
