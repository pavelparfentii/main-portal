<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoralisAPI extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $dates = [
        'last_call'
    ];

    /**
     * Get newest api key.
     *
     * @return string
     */
    public static function getApiKey(): string
    {
        /** @var self $account */
        $account = self::query()->oldest('last_call')->first();

        $account->last_call = now();
        $account->save();

        return $account->api_key;
    }
}
