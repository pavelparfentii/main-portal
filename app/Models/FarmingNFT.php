<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmingNFT extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected $casts = ['token_balance_last_update'=> 'datetime'];

}
