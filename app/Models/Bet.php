<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_telegrams';

    protected $guarded = false;

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }
}
