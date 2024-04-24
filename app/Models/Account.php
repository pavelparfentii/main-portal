<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\ConstantValues;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Account extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at', 'wallet', 'auth_id'];

    protected $dates = ['blocked_until'];

//    protected $appends = ['is_friend'];

    protected static function booted()
    {
        static::created(function ($account) {

            //create previous week and attach to new account
            $currentDate = Carbon::now();
            $startOfWeek = $currentDate->copy()->subWeek()->startOfWeek();
            $endOfWeek = $currentDate->copy()->subWeek()->endOfWeek();

            $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

            $previousWeek = Week::where('account_id', $account->id)
                ->where('start_date', '<=', $startOfWeek)
                ->where('end_date', '>=', $endOfWeek)
                ->where('active', false)
                ->first();


            if (!$previousWeek) {

                $previousWeek = $account->weeks()->create([
                    'week_number' => $previousWeekNumber,
                    'start_date' => $startOfWeek->toDateString(),
                    'end_date' => $endOfWeek->toDateString(),
                    'active' => false,
                    'total_points'=>0.000,
                    'points' => 0.000,
                    'claim_points' => 0.000,
                    'claimed' => true
                ]);
            }

            $currentWeek = Week::getCurrentWeekForAccount($account);
            if ($account->role === ConstantValues::safesoul_og_patrol_role) {
                $safeSoul = new SafeSoul([
                    'account_id' => $account->id,
                    'week_id' => $currentWeek->id,
                    'points' => ConstantValues::safesoul_OG_patrol_points,
                    'comment' => 'роль Ог патрульный',
                    'query_param' => ConstantValues::safesoul_og_patrol_role
                ]);
                $currentWeek->safeSouls()->save($safeSoul);
                $currentWeek->increment('points', ConstantValues::safesoul_OG_patrol_points);
            }
            if ($account->role === ConstantValues::safesoul_patrol_role) {
                $safeSoul = new SafeSoul([
                    'account_id' => $account->id,
                    'week_id' => $currentWeek->id,
                    'points' => ConstantValues::safesoul_patrol_points,
                    'comment' => 'роль патрульный',
                    'query_param' => ConstantValues::safesoul_patrol_role
                ]);
                $currentWeek->safeSouls()->save($safeSoul);
                $currentWeek->increment('points', ConstantValues::safesoul_patrol_points);
            }
        });

        static::saved(function ($account) {

            //something strange here
//        $currentRole = $account->role;
//
//        $id = $account->id;
//        if ($account->isDirty('role')) {
//            $patrol = DB::table('safe_souls')
//                ->where('account_id', $id)
//                ->where('query_param', 'patrol' )
//                ->first();
//            $og_patrol = DB::table('safe_souls')
//                ->where('account_id', $id)
//                ->where('query_param', 'og_patrol' )
//                ->first();
//
//            if(isset($patrol) && $currentRole === ConstantValues::safesoul_og_patrol_role){
//                $safeSoul = new SafeSoul([
//                    'account_id' => $id,
//                    'points' => ConstantValues::safesoul_OG_patrol_points,
//                    'comment' => 'получил роль Ог патрульный, потерял очки за роль патруль',
//                    'query_param' => ConstantValues::safesoul_og_patrol_role
//                ]);
//                $account->safeSouls()->save($safeSoul);
//                $safeSoul = new SafeSoul([
//                    'account_id'=>$id,
//                    'points'=>-ConstantValues::safesoul_patrol_points,
//                    'comment'=> 'удалены очки за роль патрульный',
//                    'query_param'=>ConstantValues::safesoul_patrol_role
//                ]);
//                $account->safeSouls()->save($safeSoul);
//            }elseif (isset($og_patrol) && $currentRole === ConstantValues::safesoul_patrol_role){
//                $safeSoul = new SafeSoul([
//                    'account_id' => $id,
//                    'points' => -ConstantValues::safesoul_OG_patrol_points,
//                    'comment' => 'получил роль патрульный, потерял очки за роль Ог патруль',
//                    'query_param' => ConstantValues::safesoul_og_patrol_role
//                ]);
//                $account->safeSouls()->save($safeSoul);
//                $safeSoul = new SafeSoul([
//                    'account_id' => $id,
//                    'points' => ConstantValues::safesoul_patrol_points,
//                    'comment' => 'получил роль патрульный',
//                    'query_param' => ConstantValues::safesoul_patrol_role
//                ]);
//                $account->safeSouls()->save($safeSoul);
//            }elseif (!isset($og_patrol) && !isset($patrol) && !is_null($currentRole)){
//
//                if($currentRole === ConstantValues::safesoul_og_patrol_role){
//                    $safeSoul = new SafeSoul([
//                        'account_id'=>$id,
//                        'points'=>ConstantValues::safesoul_OG_patrol_points,
//                        'comment'=> 'Ог патрульный',
//                        'query_param'=>ConstantValues::safesoul_og_patrol_role
//                    ]);
//                    $account->safeSouls()->save($safeSoul);
//                }
//                if($currentRole === ConstantValues::safesoul_patrol_role){
//                    $safeSoul = new SafeSoul([
//                        'account_id'=>$id,
//                        'points'=>ConstantValues::safesoul_patrol_points,
//                        'comment'=> 'патрульный',
//                        'query_param'=>ConstantValues::safesoul_patrol_role
//                    ]);
//                    $account->safeSouls()->save($safeSoul);
//                }
//
//            }
//        }

        });

        static::retrieved(function($account){
            if ($account->blocked_until && $account->blocked_until->isPast()) {
                $account->update(['code_attempts' => 0, 'blocked_until' => null]);
            }
        });
    }

    public function updateRoleAndAdjustPoints($newRole)
    {
        $originalRole = $this->role;

        // Proceed only if the role is actually changing
        if ($originalRole == $newRole) {
            return; // No change, so no need to adjust points
        }

        $currentWeek = Week::getCurrentWeekForAccount($this);
        if(is_null($originalRole) && $newRole === ConstantValues::safesoul_og_patrol_role){

            $safeSoul = new SafeSoul([
                'account_id' => $this->id,
//                            'week_id' => $currentWeek->id,
                'points' => ConstantValues::safesoul_OG_patrol_points,
                'comment' => 'Ог патрульный',
                'query_param' => ConstantValues::safesoul_OG_patrol_points
            ]);

            $currentWeek->safeSouls()->save($safeSoul);
            $currentWeek->increment('points', ConstantValues::safesoul_OG_patrol_points);

        }elseif(is_null($originalRole) && $newRole == ConstantValues::safesoul_patrol_role){
            $safeSoul = new SafeSoul([
                'account_id' => $this->id,
//                            'week_id' => $currentWeek->id,
                'points' => ConstantValues::safesoul_patrol_points,
                'comment' => 'роль патрульный',
                'query_param' => ConstantValues::safesoul_patrol_role
            ]);
            $currentWeek->safeSouls()->save($safeSoul);
            $currentWeek->increment('points', ConstantValues::safesoul_patrol_points);
            $currentWeek->increment('total_points', ConstantValues::safesoul_patrol_points);
        }

        DB::transaction(function () use ($newRole, $originalRole) {

            // Update the role
            $this->role = $newRole;
            $this->save();

            $currentWeek = Week::getCurrentWeekForAccount($this);

            // Check for existing points entries related to patrol roles
            $patrolEntryExists = SafeSoul::where('query_param', ConstantValues::safesoul_patrol_role)
                ->where('account_id', $this->id)
                ->exists();
            $ogPatrolEntryExists = SafeSoul::where('query_param', ConstantValues::safesoul_og_patrol_role)
                ->where('account_id', $this->id)
                ->exists();

            // Handle the transition from patrol to og_patrol
            if ($originalRole === ConstantValues::safesoul_patrol_role && $newRole === ConstantValues::safesoul_og_patrol_role) {
                if ($patrolEntryExists) {
                    $currentWeek->safeSouls()->create([

                        'account_id' => $this->id,
//                        'week_id' => $currentWeek->id,
                        'points' => -ConstantValues::safesoul_patrol_points,
                        'comment' => 'Downgraded role from патрульный, points subtracted',
                        'query_param' => 'downgraded_' . ConstantValues::safesoul_patrol_role,

                    ]);
                    $currentWeek->decrement('points', ConstantValues::safesoul_patrol_points);
                    $currentWeek->decrement('total_points', ConstantValues::safesoul_patrol_points);
                }
                $currentWeek->safeSouls()->create([
                    'account_id' => $this->id,
                    'points' => ConstantValues::safesoul_OG_patrol_points,
                    'comment' => 'Upgraded to role Ог патрульный, points added',
                    'query_param' => ConstantValues::safesoul_og_patrol_role,
                ]);
                SafeSoul::where('query_param', ConstantValues::safesoul_patrol_role)
                    ->where('account_id', $this->id)
                    ->delete();

                $currentWeek->increment('points', ConstantValues::safesoul_OG_patrol_points);
                $currentWeek->increment('total_points', ConstantValues::safesoul_OG_patrol_points);

            } // Handle the transition from og_patrol to patrol
            elseif ($originalRole === ConstantValues::safesoul_og_patrol_role && $newRole === ConstantValues::safesoul_patrol_role) {
                if ($ogPatrolEntryExists) {
                    $currentWeek->safeSouls()->create([
                        'account_id' => $this->id,
                        'points' => -ConstantValues::safesoul_OG_patrol_points,
                        'comment' => 'Downgraded from Ог патрульный, points subtracted',
                        'query_param' =>'downgraded_' .  ConstantValues::safesoul_og_patrol_role,
                    ]);
                    $currentWeek->decrement('points', ConstantValues::safesoul_OG_patrol_points);
                }
                $currentWeek->safeSouls()->create([
                    'account_id' => $this->id,
                    'points' => ConstantValues::safesoul_patrol_points,
                    'comment' => 'Upgraded to патрульный, points added',
                    'query_param' => ConstantValues::safesoul_patrol_role,
                ]);

                SafeSoul::where('query_param', ConstantValues::safesoul_og_patrol_role)
                    ->where('account_id', $this->id)
                    ->delete();
                $currentWeek->increment('points', ConstantValues::safesoul_patrol_points);
                $currentWeek->increment('total_points', ConstantValues::safesoul_patrol_points);

            }

        });
    }

    public function incrementPoints(string $type, ?string $tweet_id = null, ?string $comment_id = null): void
    {
        $currentWeek = Week::getCurrentWeekForAccount($this);

        if ($type === 'likes') {

            $queryParam = '3projects_likes_tweet_id_'. $tweet_id;
            $existingTwitter = Twitter::where('query_param', $queryParam)
                ->where('account_id', $this->id)
                ->first();

            if(!$existingTwitter){
                $twitter = new Twitter([
                    'account_id'=>$this->id,
                    'claim_points' => ConstantValues::twitter_projects_tweet_likes_points,
                    'comment' => 'лайк нашего твита tweet_id=' . $tweet_id,
                    'query_param' => $queryParam
                ]);
                $currentWeek->twitters()->save($twitter);
                $currentWeek->increment('claim_points', ConstantValues::twitter_projects_tweet_likes_points);

                $twitterAction = $this->getTwitterAction();
                if ($twitterAction) {
                    $twitterAction->increment('likes');
                }
            }

        } elseif ($type === 'retweets') {

            $queryParam = '3projects_retweets_tweet_id_'. $tweet_id;

            $existingTwitter = Twitter::where('query_param', $queryParam)
                ->where('account_id', $this->id)
                ->first();

            if(!$existingTwitter){
                $twitter = new Twitter([
                    'account_id'=>$this->id,
                    'claim_points' => ConstantValues::twitter_projects_tweet_retweet_points,
                    'comment' => 'ретвит нашего твита tweet_id=' . $tweet_id,
                    'query_param' => $queryParam
                ]);
                $currentWeek->twitters()->save($twitter);
                $currentWeek->increment('claim_points', ConstantValues::twitter_projects_tweet_retweet_points);

                $twitterAction = $this->getTwitterAction();
                if ($twitterAction) {
                    $twitterAction->increment('retweets');
                }
            }


        } elseif ($type === 'quotes') {

            $queryParam = '3projects_quotes_tweet_id_'. $tweet_id;

            $existingTwitter = Twitter::where('query_param', $queryParam)
                ->where('account_id', $this->id)
                ->first();

            if(!$existingTwitter){
                $twitter = new Twitter([
                    'account_id'=>$this->id,
                    'claim_points' => ConstantValues::twitter_projects_tweet_quote_points,
                    'comment' => 'Ретвит нашего твита с комментарием  tweet_id=' . $tweet_id . ' quote_id=' . $comment_id,
                    'query_param' => $queryParam
                ]);

                $currentWeek->twitters()->save($twitter);
                $currentWeek->increment('claim_points', ConstantValues::twitter_projects_tweet_quote_points);

                $twitterAction = $this->getTwitterAction();
                if ($twitterAction) {
                    $twitterAction->increment('quotes');
                }
            }


        } elseif ($type === 'comments') {

            $queryParam = '3projects_comments_tweet_id_'. $tweet_id;

            $existingTwitter = Twitter::where('query_param', $queryParam)
                ->where('account_id', $this->id)
                ->first();

            if(!$existingTwitter){
                $twitter = new Twitter([
                    'account_id'=>$this->id,
                    'claim_points' => ConstantValues::twitter_projects_tweet_comment_points,
                    'comment' => 'Коммента нашего твита с комментарием tweet_id=' . $tweet_id . ' comment_id=' . $comment_id,
                    'query_param' => $queryParam
                ]);
                $currentWeek->twitters()->save($twitter);
                $currentWeek->increment('claim_points', ConstantValues::twitter_projects_tweet_comment_points);

                $twitterAction = $this->getTwitterAction();
                if ($twitterAction) {
                    $twitterAction->increment('comments');
                }
            }
        }

    }

    public function weeks(): HasMany
    {
        return $this->hasMany(Week::class);
    }

    public function action(): HasOne
    {
        return $this->hasOne(TwitterAction::class);
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

    public function downloadTwitterAvatar($result): ?string
    {
        $TWITTER_AVATAR_PATH = 'twitter/avatars';

        try {

            if (isset($result)) {

                $url = $result;

                $contents = file_get_contents($url);

                $filename = $TWITTER_AVATAR_PATH. '/'. md5($url) . '.' . pathinfo($url, PATHINFO_EXTENSION);

                Storage::disk('public')->put($filename, $contents);

                $fullUrl = url('storage/' .$filename);
                return $fullUrl;
            }

        } catch (Exception $exception) {
            Log::error('Error while loading twitter avatar: ' . $exception->getMessage());
            return null;
        }

        return null;
    }

    public function getTwitterAction(){
        $twitterAction = TwitterAction::where('account_id', $this->id)->first();

        if(!$twitterAction){
            $twitterAction = $this->action()->create();
        }
        return $twitterAction;
    }



//    public function getIsFriendAttribute()
//    {
//
//        return $this->friends()->where('friend_id', $this->id)->exists();
//    }

    public function adjustPointsAndWipeSafesouls()
    {
        $accounts = Account::with('safeSouls')->get();

        foreach ($accounts as $account) {

            $pointsToDeduct = $account->safesouls->sum('points');


            $account->total_points -= $pointsToDeduct;
            $account->save();
        }

        // Wipe the safesouls table after adjustments
        SafeSoul::query()->delete();
    }


}
