<?php

namespace App\Http\Resources;

use App\Models\DiscordRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

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

//            'discord_roles' => $this->whenLoaded('discordRoles', function () {
//                return $this->discordRoles->map(function ($role) {
//                    return ['role_id' => $role->role_id, 'name' => $role->name, 'position'=>$role->position, 'color'=>$role->color];
//                });
//            }, []),
            'discord_roles'=>DiscordRoleResource::collection($this->whenLoaded('discordRoles')),

//            'friends' => $this->whenLoaded('friends', function (){
//                $allFriends = $this->friends->merge($this->whenLoaded('followers'));
//                return $allFriends->map(function ($friend){
//                    // Calculate the rank for each friend/follower
//                    $friendRank = DB::table('accounts')
//                            ->where('total_points', '>', $friend->total_points)
//                            ->count() + 1;
//                    $friend->rank = $friendRank; // Attach the calculated rank
//
//                    return new AccountResource($friend); // Use AccountResource for each friend/follower
//                });
//            }),
//            'friend'=> $this->relationLoaded('followers') && $this->followers->isNotEmpty(),
            'friend'=>$this->friend,
            'team'=>new TeamResource($this->whenLoaded('team'))
        ];
    }
}
