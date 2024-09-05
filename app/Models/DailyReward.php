<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReward extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_telegrams';

    protected $guarded = false;

    protected $casts = [
        'previous_login' => 'datetime',
        'update_reward_at'=> 'datetime'
    ];
}
