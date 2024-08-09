<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WinnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    protected $totalAmount;

    public function __construct($resource, $totalAmount)
    {
        parent::__construct($resource);
        $this->totalAmount = $totalAmount;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->telegram->id,
            'telegram_id' => $this->telegram->telegram_id,
            'telegram_username' => $this->telegram->telegram_username,
            'first_name' => $this->telegram->first_name,
            'last_name'=>$this->telegram->last_name,
            'avatar'=>$this->telegram->avatar,
            'amount'=>$this->totalAmount,
        ];
    }
}
