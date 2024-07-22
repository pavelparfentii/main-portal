<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

//    protected static function booted()
//    {
//        static::retrieved( function($telegram){
//            $connection = $telegram->getConnectionName();
//
//
//            if($connection === 'pgsql_telegrams'){
//
//                $lastNotification = $telegram->last_notification_at ? Carbon::parse($telegram->last_notification_at) : null;
//
//                $now = Carbon::now();
//
//                $days1 = 15;
//                $days2 = 30;
//                $days3 = 45;
//                $days4 = 60;
//
////                $twoHoursAgo = $now->subHours(2);
////                $threeHoursAgo = $now->subHours(3);
////                $fourHoursAgo = $now->subHours(4);
////                $fiveHoursAgo = $now->subHours(5);
//
//                if($now->diffInMinutes($lastNotification) >= $days1 && $now->diffInMinutes($lastNotification) < $days2){
//                    $telegram->update(['notification_sent' => false, 'notification_stage'=>0]);
//                }elseif ($now->diffInMinutes($lastNotification) >= $days2 && $now->diffInMinutes($lastNotification) < $days3){
//                    $telegram->update(['notification_sent' => false, 'notification_stage'=>0]);
//                }elseif($now->diffInMinutes($lastNotification) >= $days3 && $now->diffInMinutes($lastNotification) < $days4){
//                    $telegram->update(['notification_sent' => false, 'notification_stage'=>0]);
//                }
////
////                if(Carbon::parse($telegram->next_update_at) < $twoHoursAgo ){
////                    $telegram->update(['notification_sent' => false, 'last_notification_at' => null]);
////                }
//            }
//        });
//    }


}
