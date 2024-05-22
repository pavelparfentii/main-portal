<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Telegram extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected $hidden = ['updated_at', 'created_at'];


}
