<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
//            'wallet' => $this->wallet,
            'twitter_username' => $this->twitter_username,
            'total_points' => $this->total_points,
            'twitter_name' => $this->twitter_name,
            'twitter_avatar' => $this->twitter_avatar,
            'rank'=>$this->rank,
            'current_user'=>$this->current_user,
            'discord_roles' => $this->discordRoles->map(function ($role) {
                // Return only what you need from the role
                return ['role_id' => $role->roles_id, 'name' => $role->name];
            }),
        ];
    }
}
