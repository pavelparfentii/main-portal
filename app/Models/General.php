<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class General extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }
}
