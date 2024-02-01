<?php

namespace App\Models;

use App\Events\SafeSoulCreationEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafeSoul extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    protected static function booted()
    {
        static::created(function($safe){
            $accountId = $safe->account_id;
            event(new SafeSoulCreationEvent($safe, $accountId));
        });
    }
}
