<?php

namespace App\Models;

use App\Events\SafeSoulCreationEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafeSoul extends Model
{
    use HasFactory;

    protected $guarded = [];

//    public function account()
//    {
//        return $this->belongsTo(Account::class);
//    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    protected static function booted()
    {
        static::created(function($safe){
            if(is_null($safe->query_param)){
                $weekId = $safe->week_id;
                event(new SafeSoulCreationEvent($safe, $weekId));
            }

        });
    }
}
