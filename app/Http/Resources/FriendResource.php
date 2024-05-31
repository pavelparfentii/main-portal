<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendResource extends JsonResource
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
            'week_points' => $this->week_points,
            'twitter_name' => $this->twitter_name,
            'twitter_avatar' => $this->twitter_avatar,
            'rank'=>$this->rank,
            'current_user'=>$this->current_user,
            'discord_roles'=>DiscordRoleResource::collection($this->whenLoaded('discordRoles')),
            'team' => new TeamResource($this->whenLoaded('team'))
           ];
    }
}
