<?php

namespace App\Models;

use App\Events\DigitalAnimalsCreationEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAnimal extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    protected static function booted()
    {
        static::created(function($animal){
            if(is_null($animal->query_param)){
                $weekId = $animal->week_id;
                event(new DigitalAnimalsCreationEvent($animal, $weekId));
            }

        });
    }
}
