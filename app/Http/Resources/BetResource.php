<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'telegram' => [
                'id' => $this->account->telegram->id,
                'telegram_id' => $this->account->telegram->telegram_id,
                'telegram_username' => $this->account->telegram->telegram_username,
                'first_name' => $this->account->telegram->first_name,
                'last_name'=>$this->account->telegram->last_name,
                'avatar'=>$this->account->telegram->avatar,

            ]
        ];
    }
}
