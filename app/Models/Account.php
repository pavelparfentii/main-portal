<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\ConstantValues;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Account extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];

//    protected $appends = ['is_friend'];

    protected static function booted()
    {
        static::created(function ($account){
            if($account->role === ConstantValues::safesoul_og_patrol_role){
                $safeSoul = new SafeSoul([
                    'account_id'=>$account->id,
                    'points'=>ConstantValues::safesoul_OG_patrol_points,
                    'comment'=> 'Ог патрульный',
                    'query_param'=>ConstantValues::safesoul_og_patrol_role
                ]);
                $account->safeSouls()->save($safeSoul);
            }
            if($account->role === ConstantValues::safesoul_patrol_role){
                $safeSoul = new SafeSoul([
                    'account_id'=>$account->id,
                    'points'=>ConstantValues::safesoul_patrol_points,
                    'comment'=> 'патрульный',
                    'query_param'=>ConstantValues::safesoul_patrol_role
                ]);
                $account->safeSouls()->save($safeSoul);
            }
        });

        static::saved(function ($account){

//            $originalRole = $account->getOriginal('role');
            $currentRole = $account->role;

            $id = $account->id;
            if ($account->isDirty('role')) {
                $patrol = DB::table('safe_souls')
                    ->where('account_id', $id)
                    ->where('query_param', 'patrol' )
                    ->first();
                $og_patrol = DB::table('safe_souls')
                    ->where('account_id', $id)
                    ->where('query_param', 'og_patrol' )
                    ->first();

                if(isset($patrol) && $currentRole === ConstantValues::safesoul_og_patrol_role){
                    $safeSoul = new SafeSoul([
                        'account_id' => $id,
                        'points' => ConstantValues::safesoul_OG_patrol_points,
                        'comment' => 'получил роль Ог патрульный, потерял очки за роль патруль',
                        'query_param' => ConstantValues::safesoul_og_patrol_role
                    ]);
                    $account->safeSouls()->save($safeSoul);
                    $safeSoul = new SafeSoul([
                        'account_id'=>$id,
                        'points'=>-ConstantValues::safesoul_patrol_points,
                        'comment'=> 'удалены очки за роль патрульный',
                        'query_param'=>ConstantValues::safesoul_patrol_role
                    ]);
                    $account->safeSouls()->save($safeSoul);
                }elseif (isset($og_patrol) && $currentRole === ConstantValues::safesoul_patrol_role){
                    $safeSoul = new SafeSoul([
                        'account_id' => $id,
                        'points' => -ConstantValues::safesoul_OG_patrol_points,
                        'comment' => 'получил роль патрульный, потерял очки за роль Ог патруль',
                        'query_param' => ConstantValues::safesoul_og_patrol_role
                    ]);
                    $account->safeSouls()->save($safeSoul);
                    $safeSoul = new SafeSoul([
                        'account_id' => $id,
                        'points' => ConstantValues::safesoul_patrol_points,
                        'comment' => 'получил роль патрульный',
                        'query_param' => ConstantValues::safesoul_patrol_role
                    ]);
                    $account->safeSouls()->save($safeSoul);
                }elseif (!isset($og_patrol) && !isset($patrol) && !is_null($currentRole)){

                    if($currentRole === ConstantValues::safesoul_og_patrol_role){
                        $safeSoul = new SafeSoul([
                            'account_id'=>$id,
                            'points'=>ConstantValues::safesoul_OG_patrol_points,
                            'comment'=> 'Ог патрульный',
                            'query_param'=>ConstantValues::safesoul_og_patrol_role
                        ]);
                        $account->safeSouls()->save($safeSoul);
                    }
                    if($currentRole === ConstantValues::safesoul_patrol_role){
                        $safeSoul = new SafeSoul([
                            'account_id'=>$id,
                            'points'=>ConstantValues::safesoul_patrol_points,
                            'comment'=> 'патрульный',
                            'query_param'=>ConstantValues::safesoul_patrol_role
                        ]);
                        $account->safeSouls()->save($safeSoul);
                    }

//            if($originalRole === ConstantValues::safesoul_og_patrol_role && ($currentRole === 'patrol' || $currentRole ==='observer' || $currentRole ==='scout')) {
//                SafeSoul::create([
//                    'account_id' => $account->id,
//                    'points' => -ConstantValues::safesoul_OG_patrol_points,
//                    'comment' => 'понижена роль Ог патрульный',
//                    'query_param' => ConstantValues::safesoul_og_patrol_role
//                ]);
//            }elseif ($originalRole === ConstantValues::safesoul_patrol_role && $currentRole === ConstantValues::safesoul_og_patrol_role){
//                SafeSoul::create([
//                    'account_id' => $account->id,
//                    'points' => -ConstantValues::safesoul_patrol_points,
//                    'comment' => 'получил роль Ог патрульный, потерял очки за роль патруль',
//                    'query_param' => ConstantValues::safesoul_patrol_role
//                ]);
//                SafeSoul::create([
//                    'account_id' => $account->id,
//                    'points' => ConstantValues::safesoul_OG_patrol_points,
//                    'comment' => 'получил роль Ог патрульный',
//                    'query_param' => ConstantValues::safesoul_og_patrol_role
//                ]);
                }
            }

        });
    }

    public function incrementPoints(string $type, ?string $tweet_id =null, ?string $comment_id=null): void
    {
        if($type === 'likes'){
            $twitter = new Twitter([
                'points' => ConstantValues::twitter_projects_tweet_likes_points,
                'comment' => 'лайк нашего твита tweet_id=' . $tweet_id,
                'query_param' => '3projects_likes'
            ]);
            $this->twitters()->save($twitter);
        }elseif ($type === 'retweets'){
            $twitter = new Twitter([
                'points' => ConstantValues::twitter_projects_tweet_retweet_points,
                'comment' => 'ретвит нашего твита tweet_id=' . $tweet_id,
                'query_param' => '3projects_retweets'
            ]);
            $this->twitters()->save($twitter);
        }elseif ($type === 'quotes'){
            $twitter = new Twitter([
                'points' => ConstantValues::twitter_projects_tweet_quote_points,
                'comment' => 'Ретвит нашего твита с комментарием  tweet_id=' . $tweet_id . ' quote_id=' . $comment_id,
                'query_param' => '3projects_quotes'
            ]);
            $this->twitters()->save($twitter);
        }elseif($type === 'comments'){
            $twitter = new Twitter([
                'points' => ConstantValues::twitter_projects_tweet_quote_points,
                'comment' => 'Коммента нашего твита с комментарием tweet_id=' . $tweet_id . ' comment_id='.$comment_id,
                'query_param' => '3projects_comments'
            ]);
            $this->twitters()->save($twitter);
        }

    }

    public function animals()
    {
        return $this->hasMany(DigitalAnimal::class);
    }

    public function games()
    {
        return $this->hasMany(DigitalGame::class);
    }

    public function digitalSouls()
    {
        return $this->hasMany(DigitalSoul::class);
    }

    public function safeSouls()
    {
        return $this->hasMany(SafeSoul::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function generals()
    {
        return $this->hasMany(General::class);
    }

    public function twitters()
    {
        return $this->hasMany(Twitter::class);
    }

    public function discordRoles(): BelongsToMany
    {
        return $this->belongsToMany(DiscordRole::class, 'account_discord_role', 'account_id', 'discord_role_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Code::class);
    }

    public function invitesSent(): HasMany
    {
        return $this->hasMany(Invite::class, 'invited_by');
    }

    public function invitesReceived(): HasMany
    {
        return $this->hasMany(Invite::class, 'whom_invited');
    }

    public function friends()
    {
        return $this->belongsToMany(Account::class, 'account_friend', 'account_id', 'friend_id')->withTimestamps();
    }

    // Optionally, define the inverse relationship to get the accounts that consider this account a friend
    public function followers()
    {
        return $this->belongsToMany(Account::class, 'account_friend', 'friend_id', 'account_id')->withTimestamps();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function createdTeam(): HasOne
    {
        return $this->hasOne(Team::class, 'account_id');
    }

    public function createTeamAndAssign($teamData)
    {
        DB::transaction(function () use ($teamData) {

            if ($this->team_id) {


                $this->team_id = null;
                $this->save();
            }


            $team = Team::create($teamData);

            $this->team()->associate($team);
            $this->save();
        });
    }



//    public function getIsFriendAttribute()
//    {
//
//        return $this->friends()->where('friend_id', $this->id)->exists();
//    }



}
