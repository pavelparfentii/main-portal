<?php

namespace App\Models;

use App\Events\TwitterCreationEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Twitter extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    protected static function booted()
    {
        static::created(function($twitter){
            $accountId = $twitter->account_id;
            event(new TwitterCreationEvent($accountId, $twitter));
        });
    }
}
