<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DiscordRole extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_discord_role', 'discord_role_id', 'account_id');
    }
}
