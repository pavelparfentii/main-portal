<?php

namespace App\Traits;


use App\Events\TelegramPointsUpdated;
use App\Helpers\AuthHelperTelegram;
use App\Http\Controllers\InviteController;
use App\Http\Resources\AccountResource;
use App\Http\Resources\FriendResource;
use App\Http\Resources\TeamResource;
use App\Models\Account;
use App\Models\AccountFarm;
use App\Models\Code;
use App\Models\Invite;
use App\Models\Team;
use App\Models\Telegram;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

trait TelegramTrait{

    public function getInfo(Request $request): JsonResponse
    {
        $account = AuthHelperTelegram::auth($request);


        $token = $request->bearerToken();

        if ($account) {

            //$this->checkDailyPoints($account);

//            $userRank = DB::table('accounts')
//                    ->where('total_points', '>', $account->total_points)
//                    ->count() + 1;
//
//            $account->setAttribute('rank', $userRank);
            $account->setAttribute('current_user', true);

//            $account->setAttribute('invited', '-');

            if(!empty($account->discord_id)){
                $account->load('discordRoles');
            }

            $accountResourceArray = (new AccountResource($account))->resolve();

            $inviteCheck = DB::connection('pgsql_telegrams')
                ->table('invites')
                ->where('whom_invited', $account->id)
                ->pluck('id')
                ->toArray();

            $inviteController = new InviteController();
            $inviteCode = $inviteController->activateCodeTelegram($account);

            $isBlocked = $account->blocked_until;

            $accountResourceArray['claimed_points']=  null;

            $accountResourceArray['invites_count'] = $account->invitesSent()->count() ?? 0;
            $accountResourceArray['invited'] = !empty($inviteCheck) ? true : false;
            $accountResourceArray['invite_code']=$inviteCode;
            $accountResourceArray['isBlocked'] = !is_null($isBlocked) ? true : false;
//            $accountResourceArray['total_points']=$total_points;
            $accountResourceArray['total_points']=$account->total_points;
            $accountResourceArray['daily_farm']=0;
            $accountResourceArray['isAmbassador']=$account->ambassador;

            if ($account->telegram()->exists()) {
                $accountResourceArray['telegram_next_update'] = Carbon::parse($account->telegram->next_update_at)->setTimezone('UTC')->toISOString();
            } else {
                $accountResourceArray['telegram_next_update'] = null;
            }



            return response()->json([
                'user' => $accountResourceArray,
                'global'=>[
                    'total_users'=> DB::connection('pgsql_telegrams')->table('accounts')->count(),
                    'total_teams'=>DB::connection('pgsql_telegrams')->table('teams')->count(),
                    'friends'=>!empty($account->twitter_username) ? $account->friends->count() : null,
                ]
            ]);

        }elseif ($token && !$account){

            return response()->json(['error' => 'token expired or wrong'], 401);

        } else{
            return response()->json([
                'user' => null,
                'global'=>[
                    'total_users'=> DB::connection('pgsql_telegrams')->table('accounts')->count(),
                    'total_teams'=>DB::connection('pgsql_telegrams')->table('teams')->count(),
                    'friends'=>null
                ]
            ]);
        }
    }

    public function getData(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        $period = $request->input('period');

        $cacheKey = $this->getCacheKey($period, $account);

        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        if($period === 'total'){
            $friendIds = $account ? $account->friends->pluck('id')->toArray() : [];

            $loadRelations = ['discordRoles', 'friends', 'team.accounts'];
            // If the current user does not have a twitter_username, adjust the relations to be loaded

            $topAccounts = Account::on('pgsql_telegrams')->with($loadRelations)
                ->leftJoin('weeks', function ($join) {
                    $join->on('accounts.id', '=', 'weeks.account_id')
                        ->where('weeks.week_number', '=', Carbon::now()->subWeek()->format('W-Y'))
                        ->where('weeks.active', '=', false);
                })
                ->select('accounts.id',
                    'accounts.wallet',
                    'accounts.twitter_username',
                    'accounts.total_points',
//                    'weeks.total_points as week_points',
                    'accounts.twitter_name',
                    'accounts.twitter_avatar',
                    'accounts.team_id',
                    'accounts.current_rank',
                    'accounts.previous_rank')
                ->orderByDesc('accounts.total_points')
                ->take(100)
                ->get();

            // Enrich the collection with rank, current_user flags, and is_friend flag
            $topAccounts->transform(function ($item, $key) use ($account, $friendIds) {
//                $item->rank =$key + 1; was
                $item->current_rank = $key + 1;
                $item->previous_rank = null;
                $item->current_user = $account && $account->id == $item->id;
                if(!empty($account->twitter_username)){
                    $item->friend = in_array($item->id, $friendIds);
                }else{
                    $item->friend = false;
                }

                $item->team = $item->team ? new TeamResource($item->team) : null;

                return $item;
            });

            $token = $request->bearerToken();

            if($token && !$account){
                return response()->json(['error' => 'token expired or wrong'], 401);
            }

            if ($account) {
                //Week::getCurrentWeekForTelegramAccount($account);

                //$previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
//                $currentUserWeekPoints = $account->weeks()
//                        ->where('week_number', $previousWeekNumber)
//                        ->where('active', false)
//                        ->first()
//                        ->total_points ?? 0;

                $userRank = DB::connection('pgsql_telegrams')
                        ->table('accounts')
                        ->where('total_points', '>', $account->total_points)
                        ->count() + 1;


                if ($userRank > 1) {
                    $currentUserForTop = clone $account;
//                    $currentUserForTop->rank = $userRank;
                    $currentUserForTop->current_rank = $userRank;
                    $currentUserForTop->previous_rank = null;
                    $currentUserForTop->current_user = true;
//                    $currentUserForTop->week_points = $currentUserWeekPoints;
                    $currentUserForTop->friend = false; // The user is not a friend to themselves

                    $currentUserForTop->load($loadRelations);

                    $topAccounts->prepend($currentUserForTop);
                }
            }

//
//            return response()->json([
//                'list' => AccountResource::collection($topAccounts),
//            ]);

            $response = [
                'list' => AccountResource::collection($topAccounts),
            ];

            Cache::put($cacheKey, $response, now()->addHours(8));

            return response()->json($response);


        }elseif($period === 'week') {

            $friendIds = $account ? $account->friends->pluck('id')->toArray() : [];

            $loadRelations = ['discordRoles', 'friends', 'team.accounts'];
//            $currentWeekNumber = Carbon::now()->format('W-Y'); // Формат тиждень-рік, наприклад "03-2024"
            $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

            $topAccounts = Account::on('pgsql_telegrams')->with($loadRelations)
                ->leftJoin('weeks', function ($join) use ($previousWeekNumber) {
                    $join->on('accounts.id', '=', 'weeks.account_id')
                        ->where('weeks.week_number', '=', $previousWeekNumber)
                        ->where('weeks.active', '=', false);
                })
                ->select('accounts.id', 'accounts.wallet',
                    'accounts.twitter_username',
                    'accounts.total_points',
                    'weeks.total_points as week_points',
                    'accounts.twitter_name',
                    'accounts.twitter_avatar',
                    'accounts.team_id',
                    'accounts.current_rank',
                    'accounts.previous_rank'
                )
                ->where('weeks.total_points', '>', 0)
                ->orderByDesc('week_points')
                ->take(100)
                ->get();

            $topAccounts->transform(function ($item, $key) use ($account, $friendIds) {
                $item->current_rank = $key + 1;
                unset($item->previous_rank);
                $item->previous_rank = null;
                $item->current_user = $account && $account->id == $item->id;
                if(!empty($account->twitter_username)){
                    $item->friend = in_array($item->id, $friendIds);
                }else{
                    $item->friend = false;
                }

                $item->team = $item->team ? new TeamResource($item->team) : null;

                return $item;
            });

            $token = $request->bearerToken();

            if($token && !$account){
                return response()->json(['error' => 'token expired or wrong'], 401);
            }

            if ($account) {

                $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
                // Отримуємо очки користувача за поточний тиждень
                $currentUserWeekPoints = $account->weeks()
                        ->where('week_number', $previousWeekNumber)
                        ->where('active', false) // Враховуємо активні тижні, якщо потрібно
                        ->first()
                        ->total_points ?? 0;
//                dd($currentUserWeekPoints);
                // Розраховуємо ранг користувача на основі його очок за тиждень
                $userRankBasedOnWeekPoints = DB::connection('pgsql_telegrams')
                        ->table('accounts')
                        ->join('weeks', 'accounts.id', '=', 'weeks.account_id')
                        ->where('weeks.week_number', '=', $previousWeekNumber)
                        ->where('weeks.total_points', '>', $currentUserWeekPoints)
                        ->where('weeks.active', false)
                        ->count() + 1;

                // Якщо користувач не увійшов до топ-100
//                dd($userRankBasedOnWeekPoints);
                if ($userRankBasedOnWeekPoints > 1) {
                    $currentUserForTop = clone $account;
                    $currentUserForTop->total_points = $account->total_points; // Використовуємо очки за тиждень
                    $currentUserForTop->week_points = $currentUserWeekPoints;
//                    $currentUserForTop->rank = $userRankBasedOnWeekPoints;
//                    $currentUserForTop->current_rank = $userRankBasedOnWeekPoints;
                    $currentUserForTop->current_rank = $userRankBasedOnWeekPoints;
                    $currentUserForTop->previous_rank = null;
                    $currentUserForTop->current_user = true;
                    $currentUserForTop->friend = false;

                    $currentUserForTop->load($loadRelations);

                    // Додаємо дані поточного користувача на початок колекції
                    $topAccounts->prepend($currentUserForTop);
                }
            }

            $response = [
                'list' => AccountResource::collection($topAccounts),
            ];

            Cache::put($cacheKey, $response, now()->addMinutes(5));

            return response()->json($response);

//            return response()->json([
//                'list' => AccountResource::collection($topAccounts),
//            ]);

        } else {
            return response()->json([
                'list' => [],
            ]);
        }
    }


    public function getFriendsForAccount(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        if (!$account) {
            return response()->json(['message' => 'Non authorized'], 401);
        }

        $period = $request->input('period');

        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
        $loadRelations = ['discordRoles', 'team.accounts'];

        // Calculate total_points and week_points for the current user
        $currentUserWeekPoints = $account->weeks()
                ->where('week_number', '=', $previousWeekNumber)
                ->where('active', false)
                ->first()
                ->total_points ?? 0;

        // Calculate rank based on the selected period
        if ($period == 'total') {

            $account->week_points = $currentUserWeekPoints;
        } else {

            $account->week_points = $currentUserWeekPoints;
        }

        $account->load($loadRelations);

        // Clone the current user data for inclusion in the response
        $currentUserData = clone $account;
        $currentUserData->current_user = true;
//        $currentUserData->current_rank =$userRank;

        $allAccounts = collect([$currentUserData]);

        // Get friends and calculate their points and ranks
        $friends = $account->friends()->with($loadRelations)->get()->each(function ($friend) use ($previousWeekNumber, $period) {
            $friendWeekPoints = $friend->weeks()
                    ->where('week_number', '=', $previousWeekNumber)
                    ->where('active', false)
                    ->first()
                    ->total_points ?? 0;

            if ($period == 'total') {
                $friendRank = DB::connection('pgsql_telegrams')
                        ->table('accounts')
                        ->where('total_points', '>', $friend->total_points)
                        ->count() + 1;
                $friend->week_points = $friendWeekPoints;
            }else {
                $friendRank = DB::connection('pgsql_telegrams')
                        ->table('accounts')
                        ->join('weeks', 'accounts.id', '=', 'weeks.account_id')
                        ->where('weeks.week_number', '=', $previousWeekNumber)
                        ->where('weeks.total_points', '>', $friendWeekPoints)
                        ->where('weeks.active', false)
                        ->count()+1;
                $friend->week_points = $friendWeekPoints;
            }
            $friend->rank = $friendRank;
            $friend->current_user = false;
        });

        // Merge the sorted friends with the current user at the top
        $allAccounts = $allAccounts->merge($friends);

        // Sort all accounts based on the selected period
        if ($period == 'total') {
            $allAccounts = $allAccounts->sortByDesc('total_points')->values();
        } else {
            $allAccounts = $allAccounts->sortByDesc('week_points')->values();
        }

        return response()->json([
            'list' => FriendResource::collection($allAccounts),
        ]);
    }

    public function makeTeam(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:teams|max:255',
            'team_avatar' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $slug = \Illuminate\Support\Str::slug($request->name);
        $slugCount = Team::on('pgsql_telegrams')->where('slug', $slug)->count();
        if ($slugCount > 0) {
            $slug = "{$slug}-{$slugCount}";
        }

        $path = $request->hasFile('team_avatar')
            ? $request->file('team_avatar')->store('team_avatars', 'public')
            : null;

        $fullUrl = $path ? url('storage/' . $path) : null;
        $teamData = [
            'name' => $request->name,
            'slug' => $slug,
            'team_avatar' =>$fullUrl,
            'account_id'=> $account->id
        ];

        $account->createTeamAndAssignTelegram($teamData);

        $team = $account->team()->first();
        $team->load('creator');

        return response()->json($team);

    }

    public function joinTeam(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        $slug = $request->slug;

        $team = Team::on('pgsql_telegrams')
            ->where('slug', $slug)
            ->first();

        if ($account->team_id == $team->id) {
            return response()->json(['message' => 'You are already a member of this team'], 400);
        }

        if ($account->id === $team->creator->id) {

            $isFriendWithCreator = true;
            $creatorIsFriendWithAccount = true;
        } else {

            $isFriendWithCreator = $account->friends()->where('id', $team->account_id)->exists();

            $creatorIsFriendWithAccount = $team->creator->friends()->where('id', $account->id)->exists();
        }

        if ($isFriendWithCreator && $creatorIsFriendWithAccount) {

            if ($account->team_id) {

                $account->team_id = null;
                $account->save();
            }

            $account->team()->associate($team->id);

            $account->save();

            $team = $account->team()->first();
            $team->load('creator');
            $team->load('accounts.discordRoles');

            return response()->json($team);
        } else {
            // Return an error if the friendship condition is not met
            //UNCOMMENT WHEN READY
//            return response()->json(['message' => 'You must be friends with the team creator to join'], 403);
            if ($account->team_id) {

                $account->team_id = null;
                $account->save();
            }

            $account->team()->associate($team->id);

            $account->save();

            $team = $account->team()->first();
            $team->load('creator');
            $team->load('accounts.discordRoles');

            return response()->json($team);
        }

    }

    public function getTeamList(Request $request)
    {
        $slug = $request->slug;
        $team = Team::on('pgsql_telegrams')
            ->where('slug', $slug)
            ->with(['creator', 'accounts'])
            ->first();
        $period = $request->input('period', 'total');
        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

        if (!$team) {
            return response()->json(['message' => 'not found'], 404);
        }

        $currentUser = AuthHelperTelegram::auth($request);

        foreach ($team->accounts as $account) {

            $account->load(['weeks' => function ($query) use ($previousWeekNumber) {
                $query->where('week_number', $previousWeekNumber)->where('active', false);
            }]);
            $account->week_points = $account->weeks->sum('total_points');

        }

        // Sort accounts based on the selected period points in descending order
        if ($period === 'week') {
            $sortedAccounts = $team->accounts->sortByDesc('week_points')->values();
        } else {
            $sortedAccounts = $team->accounts->sortByDesc('total_points')->values();
        }

        // Add rank to each account
        foreach ($sortedAccounts as $index => $account) {
            $account->rank = $index + 1;
        }

        if (!$currentUser) {
            return response()->json([
                'team' => $team,
                'sorted_accounts' => $sortedAccounts,
                'total_members' => $sortedAccounts->count(),
                'total_points' => $sortedAccounts->sum('total_points')
            ], 200);
        }

        $friendIds = $currentUser ? $currentUser->friends()->pluck('id')->toArray() : [];
        $isFriendOfCreator = in_array($team->creator->id, $friendIds);

        foreach ($sortedAccounts as $account) {
            $account->friend = !empty($account->twitter_username) && in_array($account->id, $friendIds);
            $account->in_team = $currentUser && $currentUser->team_id === $team->id;
        }

        return response()->json([
            'team' => $team,
            'sorted_accounts' => $sortedAccounts,
            'total_members' => $sortedAccounts->count(),
            'total_points' => $sortedAccounts->sum('total_points'),
            'is_friend_of_creator' => $isFriendOfCreator,
            'in_team' => $currentUser && $currentUser->team_id === $team->id
        ]);
    }

    public function getTeamsList(Request $request)
    {
        $currentUser = AuthHelperTelegram::auth($request);
        $period = $request->input('period');
        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');
        $loadRelations = ['creator', 'accounts'];

        $teams = Team::on('pgsql_telegrams')->with($loadRelations)->get();

        $cacheKey = $this->generateCacheKey($currentUser, $period);

        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        foreach ($teams as $team) {
            $teamTotalPoints = 0;
            $teamWeekPoints = 0;
            foreach ($team->accounts as $account) {
                $accountWeekPoints = $account->weeks()
                    ->where('week_number', $previousWeekNumber)
                    ->where('active', false)
                    ->sum('total_points');

                $teamTotalPoints += $account->total_points;
                $teamWeekPoints += $accountWeekPoints;

                unset($account->wallet);

                if ($currentUser) {
                    $friendIds = $currentUser->friends()->pluck('id')->toArray();
                    $account->friend = !empty($account->twitter_username) && in_array($account->id, $friendIds);
                }
            }
            $team->team_total_points = $teamTotalPoints;
            $team->team_week_points = $teamWeekPoints;
        }

        // Calculate ranks for the current period
        $teamsSortedByCurrentPoints = $teams->sortByDesc('team_total_points')->values();
        foreach ($teamsSortedByCurrentPoints as $index => $team) {
            $team->current_rank = $index + 1;
        }

        // Calculate deducted points and ranks for the previous period
        foreach ($teams as $team) {
            $team->deducted_points = $team->team_total_points - $team->team_week_points;
        }

        $teamsSortedByDeductedPoints = $teams->sortByDesc('deducted_points')->values();
        foreach ($teamsSortedByDeductedPoints as $index => $team) {
            $team->previous_rank = $index + 1;
        }

        // Save the ranks to the database
        // foreach ($teams as $team) {
        //     $team->save();
        // }

        if ($period === 'total') {
            $teams = $teamsSortedByCurrentPoints;
        } else {
            $teams = $teamsSortedByDeductedPoints;
        }

        foreach ($teams as $index => $team) {
            // $team->rank = $index + 1;
            unset($team->creator->wallet);
            unset($team->deducted_points);

            if ($currentUser) {
                $friendIds = $currentUser->friends()->pluck('id')->toArray();
                $isFriendOfCreator = $currentUser->id !== $team->creator->id && in_array($team->creator->id, $friendIds);

                if (empty($currentUser->twitter_username)) {
                    $isFriendOfCreator = false;
                }

                $team->in_team = $currentUser->team_id === $team->id;
                $team->friend = $isFriendOfCreator;
            }
        }

        $result = ['list' => $teams];
//        return response()->json(['list' => $teams]);

        Cache::put($cacheKey, $result, now()->addHours(36));

        return response()->json($result);
    }

    public function leaveTeam(Request $request)
    {

        $account = AuthHelperTelegram::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        if ($account->team_id) {

            $account->team_id = null;
            $account->save();

            return response()->json(['message' => 'success'], 200);
        }
    }

    public function checkName(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:teams|max:255|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $slug = \Illuminate\Support\Str::slug($request->name);

        $teamWithSlug = Team::on('pgsql_telegrams')
            ->where('slug', $slug)
            ->first();

        if ($teamWithSlug) {

            return response()->json(['message' => 'The name has already been taken and cannot be used.'], 422) ;
        }

    }

    public function inviteUser(Request $request)
    {
        // Чий код, дані приглашающего twitter,
        $account = AuthHelperTelegram::auth($request);

        $code = $request->code;

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        if ($account->blocked_until && $account->blocked_until > now()) {

            return response()->json(['message' => 'User is blocked for 24 hours'], 429);
        }


        $maxAttempts = 3;
        $blockPeriod = now()->addHours(24);
        $checkCode = Code::on('pgsql_telegrams')
            ->where('value', strtoupper($code))
            ->first();

        if(!$checkCode ){

            $account->increment('code_attempts');

            if ($account->code_attempts >= $maxAttempts) {

                $account->update(['blocked_until' => $blockPeriod]);
                return response()->json(['message' => 'User is blocked for 24 hours'], 429);
            }

            return response()->json(['message' => 'Wrong code'], 422);
        }

        $account->update(['code_attempts' => 0, 'blocked_until'=>null]);

        $accountId = $account->id;
        $checkCodeAccountId = $checkCode->account_id;

        if($checkCode->account_id == $account->id){
            return response()->json(['message'=>'Self-invite not permitted'], 400);
        }

        $existingInvite = Invite::on('pgsql_telegrams')
            ->where('invited_by', $accountId)
            ->where('whom_invited', $checkCodeAccountId)
            ->first();

        if ($existingInvite) {
            return response()->json(['message' => 'Cross-invite not permitted'], 423);
        }

        $inviteCheck = Invite::on('pgsql_telegrams')
            ->where('whom_invited', $account->id)
            ->first();

        if(!$inviteCheck){

            $checkAcc = $checkCode->account;

            $telegramDetails = Telegram::on('pgsql_telegrams')
                ->where('account_id', $checkAcc->id)
                ->select('first_name', 'last_name', 'avatar')
                ->first();

            $invite = Invite::on('pgsql_telegrams')->create([
                'invited_by'=>$checkCode->account->id,
                'inviter_wallet'=>$checkCode->account->wallet ?? 'empty',
                'whom_invited'=>$account->id,
                'invitee_wallet'=>$account->wallet ?? 'empty',
                'code_id'=>$checkCode->id,
                'used_code'=>$checkCode->value
            ]);

            //new functionality 19.07
            $checkAcc->refSubrefAccounts()->syncWithoutDetaching([$account->id => [
                'claimed_balance' => 0.0,
                'net_income' => 0.0,
                'income' =>$account->total_points
            ]]);
            //------------------------------------
            return response()->json([
                    'id'=>$checkAcc->id,
                    'twitter_id'=>$checkAcc->twitter_id,
                    'twitter_avatar'=>$checkAcc->twitter_avatar,
                    'twitter_name'=>$checkAcc->twitter_name,
                    'twitter_username'=>$checkAcc->twitter_username,
                    'telegram_first_name'=>$telegramDetails->first_name ?? null,
                    'telegram_last_name'=>$telegramDetails->last_name ?? null,
                    'telegram_avatar'=>$telegramDetails->avatar ?? null,
                ]
            );
        }else{
            return response()->json(['message'=>'Already invited'], 423);
        }
    }

    public function getReferralsData(Request $request)
    {

        $account = AuthHelperTelegram::auth($request);


        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        event(new TelegramPointsUpdated($account));
        $invitedCount = $account->invitesSent()->count();

        $referralsList = $account->invitesSent()->get();


        $totalFirstLevelIncome = 0;
        $totalSecondLevelIncome = 0;

        $collectReferral = $referralsList->map(function ($referral) use (&$account, &$totalSecondLevelIncome) {

            $firstLevelAccount = $referral->invitee;
            $secondLevelReferrals =$firstLevelAccount->invitesSent()->pluck('whom_invited');


            $secondLevelNetIncome = DB::connection('pgsql_telegrams')
                ->table('account_referrals')
                ->where('account_id', $account->id)
                ->whereIn('ref_subref_id', $secondLevelReferrals)
                ->sum('net_income');

            $firstLevelNetIncome = DB::connection('pgsql_telegrams')
                ->table('account_referrals')
                ->where('account_id', $account->id)
                ->where('ref_subref_id', $referral->whom_invited)
                ->value('net_income');

            $firstLevelReferralClaimedIncome = DB::connection('pgsql_telegrams')
                ->table('account_referrals')
                ->where('account_id', $account->id)
                ->where('ref_subref_id', $referral->whom_invited)
                ->sum('claimed_balance');

            $secondLevelReferralsClaimedTotal = DB::connection('pgsql_telegrams')
                ->table('account_referrals')
                ->where('account_id', $account->id)
                ->whereIn('ref_subref_id', $secondLevelReferrals)
                ->sum('claimed_balance');

            $totalFirstLevelIncome = round($firstLevelNetIncome+$secondLevelNetIncome, 2);
            $totalSecondLevelIncome +=$totalFirstLevelIncome;

            $telegram = $firstLevelAccount->telegram;
            // Log

            if(!empty($telegram)){
                return [
                    'id'=>$referral->id,
                    'twitter_name' => $referral->twitter_name,
                    'twitter_avatar'=>$referral->twitter_avatar,
                    'twitter_username'=>$referral->twitter_username,
                    'total_points' => $firstLevelAccount->total_points,
                    'invited'=>$firstLevelAccount->invitesSent()->count(),
                    'referral_income'=>$totalFirstLevelIncome,
                    'referral_claimed_total'=>$firstLevelReferralClaimedIncome ?? 0,
                    '2nd_level_claimed_total'=>$secondLevelReferralsClaimedTotal ?? 0,
                    'telegram_first_name'=>$telegram->first_name,
                    'telegram_last_name'=>$telegram->last_name,
                    'telegram_avatar'=>$telegram->avatar

                ];
            }else{
                return [
                    'id'=>$referral->id,
                    'total_points' => $referral->total_points,
                    'invited'=>$firstLevelAccount->count(),
                    'referral_income'=>$totalFirstLevelIncome
                ];
            }

        })->filter(); // Filter out any null values

        $inviteReceived = $account->invitedMe()->first();
        $invitedMe = null;


        if ($inviteReceived) {

            $invitedBy = $inviteReceived->invitedBy;
            $telegramDetails = Telegram::on('pgsql_telegrams')
                ->where('account_id', $inviteReceived->invited_by)
                ->select('first_name', 'last_name', 'avatar')
                ->first();
            $invitedMe = [
                'id' => $invitedBy->id,
                'twitter_name' => $invitedBy->twitter_name ?? null,
                'twitter_avatar' => $invitedBy->twitter_avatar ?? null,
                'twitter_username'=>$invitedBy->twitter_username ?? null,
                'total_points' => $invitedBy->total_points,
                'code'=>$inviteReceived->used_code,
                'telegram_first_name'=>$telegramDetails->first_name ?? null,
                'telegram_last_name'=>$telegramDetails->last_name ?? null,
                'telegram_avatar'=>$telegramDetails->last_name ?? null,

            ];
        }

        return response()->json([
            'referrals' => $collectReferral,
            'invited_me' => $invitedMe,
            'invited'=>$invitedCount,
            'referrals_claimed'=> $account->referrals_claimed,
            'next_referrals_claim'=> $account->next_referrals_claim,
            'total_referrals_income'=>$totalSecondLevelIncome,

        ]);

    }

    public function claimIncome(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        if (!$account) {
            return response()->json(['message' => 'non authorized'], 401);
        }


        if ($account->referrals_claimed && $account->next_referrals_claim > now()) {

            return response()->json(['message' => 'Already claimed'], 429);
        }


//        $totalIncome = Invite::on('pgsql_telegrams')
//            ->where('invited_by', $account->id)
//            ->sum('accumulated_income');
        $totalIncome = DB::connection('pgsql_telegrams')
            ->table('account_referrals')
            ->where('account_id', $account->id)
            ->sum('net_income');

        $account->referrals_claimed = true;
        $account->next_referrals_claim = now()->addHours(24);;
        //for current total
        $account->total_points += $totalIncome;
        $account->save();


//        Invite::on('pgsql_telegrams')
//            ->where('invited_by', $account->id)
//            ->update(['accumulated_income' => 0.000]);


        DB::connection('pgsql_telegrams')
            ->table('account_referrals')
            ->where('ref_subref_id', $account->id)
            ->update([
//                'claimed_balance' => DB::raw('income + claimed_balance'),
                'income'=>DB::raw("income + $totalIncome")
            ]);

        DB::connection('pgsql_telegrams')
            ->table('account_referrals')
            ->where('account_id', $account->id)
            ->update([
                'claimed_balance' => DB::raw('income + claimed_balance'),
                'income'=>0
            ]);

//        DB::connection('pgsql_telegrams')
//            ->table('account_farms')
//            ->where('account_id', $account->id)
//            ->increment('total_points', $totalIncome);
//
//        DB::connection('pgsql_telegrams')
//            ->table('account_farms')
//            ->where('account_id', $account->id)
//            ->increment('daily_farm', $totalIncome);
//
//        $this->skipAllReferralsIncome($account);

        return response()->json(['message' => 'Income claimed successfully']);
    }


    private function getCacheKey($period, $account)
    {
        $accountId = $account ? $account->id : 'guest';
        return "points_data_{$period}_{$accountId}";
    }

    private function generateCacheKey($currentUser, $period): string
    {
        $userId = $currentUser ? $currentUser->id : 'guest';
        return "teams_list_{$period}_{$userId}";
    }

    private function checkDailyPoints($account)
    {
        $accountDailyFarm = DB::connection('pgsql_telegrams')
            ->table('account_farms')
            ->where('account_id', $account->id)
            ->first();

        if (!$accountDailyFarm) {
            DB::connection('pgsql_telegrams')
                ->table('account_farms')
                ->insert([
                'account_id' => $account->id,
                'daily_farm' => 5.00, // Set default value for daily_farm
                'daily_farm_last_update' => now(), // Set default value for daily_farm_last_update
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function skipAllReferralsIncome($account)
    {
        $invitesSent = $account->invitesSent()->get();

        foreach ($invitesSent as $referral) {
            $firstLevelAccount = Account::on('pgsql_telegrams')
                ->where('id', $referral->whom_invited)
                ->first();
            if($firstLevelAccount){
                DB::connection('pgsql_telegrams')->transaction(function () use ($firstLevelAccount) {
                    $dailyFarm = DB::connection('pgsql_telegrams')
                        ->table('account_farms')
                        ->where('account_id', $firstLevelAccount->id)
                        ->value('daily_farm');

                    // Додати daily_farm до total_points
                    DB::connection('pgsql_telegrams')
                        ->table('account_farms')
                        ->where('account_id', $firstLevelAccount->id)
                        ->increment('total_points', floatval($dailyFarm));


                    DB::connection('pgsql_telegrams')
                        ->table('account_farms')
                        ->where('account_id', $firstLevelAccount->id)
                        ->update(['daily_farm' => 0.0]);
                });


                $secondLevelInvites = Invite::on('pgsql_telegrams')->where('invited_by', $firstLevelAccount->id)->get();
                foreach ($secondLevelInvites as $secondLevelInvite) {
                    $secondLevelAccount = Account::on('pgsql_telegrams')->where('id', $secondLevelInvite->whom_invited)->first();

                    if($secondLevelAccount){
                        DB::connection('pgsql_telegrams')->transaction(function () use ($secondLevelAccount) {

                            $earnedPoints = DB::connection('pgsql_telegrams')
                                ->table('account_farms')
                                ->where('account_id', $secondLevelAccount->id)
                                ->value('daily_farm');

                            DB::connection('pgsql_telegrams')
                                ->table('account_farms')
                                ->where('account_id', $secondLevelAccount->id)
                                ->increment('total_points', floatval($earnedPoints));

                            DB::connection('pgsql_telegrams')
                                ->table('account_farms')
                                ->where('account_id', $secondLevelAccount->id)
                                ->update(['daily_farm' => 0.0]);
                        });
                    }
                }
            }
        }
    }


}
