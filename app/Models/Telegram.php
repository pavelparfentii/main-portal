<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telegram extends Model
{
    use HasFactory;

    protected $guarded = false;


    protected $hidden = ['updated_at', 'created_at'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }


}
