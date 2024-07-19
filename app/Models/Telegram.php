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

    protected static function booted()
    {
        static::retrieved( function($telegram){
            $connection = $telegram->getConnectionName();

            if($connection === 'pgsql_telegrams'){
                if(Carbon::parse($telegram->next_update_at) < now()->addDays(3) ){
                    $telegram->update(['notification_sent' => false, 'last_notification_at' => null]);
                }
            }
        });
    }


}
