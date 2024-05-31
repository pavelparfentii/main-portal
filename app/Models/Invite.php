<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invite extends Model
{
    use HasFactory;

    protected $guarded = [];

    //invited_by
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'invited_by');
    }
    //whom_invited
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'whom_invited');
    }

    public function invitedBy()
    {
        return $this->belongsTo(Account::class, 'invited_by');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'code_id');
    }
}
