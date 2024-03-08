<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
//            'creator_id' => $this->creator->id ?? null,
            'team_avatar'=> $this->team_avatar,
            'members' => AccountResource::collection($this->whenLoaded('accounts')),

        ];
    }
}
