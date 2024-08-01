<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramSecondary extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected $table = 'telegrams';

    protected $connection = 'pgsql_telegrams'; // Підключення до вторинної бази даних

    protected $hidden = ['updated_at', 'created_at'];

    protected $dates = ['last_notification_at'];

    protected $casts = [
        'next_update_at' => 'datetime',
        'notification_sent' => 'boolean',
    ];


    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountSecondary::class);
    }
}
