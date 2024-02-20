<?php

namespace App\Models;

use App\Events\DigitalAnimalsCreationEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalAnimal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    protected static function booted()
    {
        static::created(function($animal){
            $accountId = $animal->account_id;
            event(new DigitalAnimalsCreationEvent($animal, $accountId));
        });
    }
}
