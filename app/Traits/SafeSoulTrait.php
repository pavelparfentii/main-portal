<?php
namespace App\Traits;

use App\ConstantValues;
use App\Models\Account;
use App\Models\DiscordRole;
use App\Models\SafeSoul;
use App\Models\Week;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

trait SafeSoulTrait{

//    private string $safesoul = 'http://127.0.0.1:8000/';
//    private string $safesoul = 'https://safesoul.test-dev.site/';
    private string $safesoul = 'https://api.safesoul.club/';

//    public function getAccountsUpdate()
//    {
//
//        try {
//            $response = Http::withHeaders([
//                'Authorization' => 'Bearer ux9AgzrHauOq6sF8kZSetP4eCAB7z2OoxLOQP1fpQCZflMrPBQdUVdQehCdGDrPZ',
//            ])->timeout(45)->get( $this->safesoul .'api/airdrop/user/info');
//
//            if($response->status() ==200){
//
//                $data = $response->json();
////                Log::info($data);
//                if(isset($data)){
//                    foreach ($data as $item){
//
//                            $account = Account::where('wallet', $item['wallet'])->first();
//                            if($account){
//                                Week::getCurrentWeekForAccount($account);
//                                usleep(5000);
//                                $role = isset($account->discord_id) || $item['discord_id'] ? $this->checkRole($account->discord_id) : null;
//                                if(isset($account->discord_id) || $item['discord_id']){
//                                    $account->updateRoleAndAdjustPoints($role);
//                                }
//                                $account->update([
//                                    'twitter_username'=>$item['twitter_username'],
//                                    'twitter_name'=>$item['twitter_name'],
//                                    'twitter_avatar'=> isset($item['twitter_avatar']) ? $this->downloadTwitterAvatar($item['twitter_avatar']) : null,
//                                    'twitter_id'=>$item['twitter_id'],
//                                    'role'=>$role,
//                                    'discord_id'=>$item['discord_id'],
//                                    'auth_id'=>$item['auth_id']
//                                ]);
//
//
//                                $account->save();
//
//
//                            }else{
//
//                                $account = new Account([
//                                    'wallet'=> $item['wallet'],
//                                    'twitter_username'=>$item['twitter_username'],
//                                    'twitter_name'=>$item['twitter_name'],
//                                    'twitter_avatar'=>isset($item['twitter_avatar']) ? $this->downloadTwitterAvatar($item['twitter_avatar']) : null,
//                                    'twitter_id'=>$item['twitter_id'],
////                                'role'=>$item['role'],
//                                    'auth_id'=>$item['auth_id'],
//                                    'discord_id'=>$item['discord_id']
//                                ]);
//                                $role = isset($account->discord_id) || $item['discord_id'] ? $this->checkRole($account->discord_id) : null;
//                                $account->role = $role;
//                                $account->save();
//
//                            }
//
//
//                    }
//                }
//                return $response->json(['ok'=>'sdsfd'],200);
//            }else{
//                Log::info('getAccountsUpdate error: '. $response->status());
//            }
//        } catch (\Exception $e){
//            Log::info('getAccountsUpdate error: '. $e);
//        }
//
//    }

    public function getAccountsUpdate()
    {
        $accounts = Account::whereNotNull('discord_id')->cursor();

        foreach ($accounts as $account){
            try {
                $role = $this->checkRole($account->discord_id);
//                sleep(1);
                $account->updateRoleAndAdjustPoints($role);
            }catch (\Exception $e){
                Log::info('getAccountsUpdate error: '. $e);
            }
        }
    }

    public function getDiscordRolesWithNamesForAccounts()
    {
        $accounts = Account::whereNotNull('discord_id')->get();

        foreach ($accounts as $account){
            $process = new Process([
                'node',
                base_path('node/getDiscordRolesWithNames.js'),
                $account->discord_id,
            ]);
            usleep(500000);
            $process->run();
            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput());
                if($data->state === 'success' && !is_null($data->data)){
                    if(is_array($data->data) && count($data->data)>0){
                        $roles = $data->data;
                        $roleIds = [];
                        foreach ($roles as $role) {

                            $existingRole = DiscordRole::updateOrCreate(
                                ['role_id' => $role->id],
                                ['name' => $role->name]
                            );
                            // Collect role IDs to sync later
                            $roleIds[] = $existingRole->id;
                        }

                        // Now sync the roles with the account
                        $account->discordRoles()->sync($roleIds);
                    }
                }
            }else{
                Log::info('Process error getDiscordRole function:' . $process->getErrorOutput());
            }
        }
    }

    public function getActivity()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ux9AgzrHauOq6sF8kZSetP4eCAB7z2OoxLOQP1fpQCZflMrPBQdUVdQehCdGDrPZ',
            ])->get( $this->safesoul .'api/airdrop/user/activity');

            if($response->status() ==200){
                $data = $response->json();

                if(isset($data)){
                    foreach ($data as $wallet){
                        $account = Account::where('wallet', $wallet)->first();
                        if($account){
                            $currentWeek = Week::getCurrentWeekForAccount($account);

                            $safeSoul = new SafeSoul([
                                'account_id' => $account->id,
//                                'week_id' => $currentWeek->id,
                                'claim_points' => ConstantValues::safesoul_activity_points,
                                'comment'=> 'активность в сейфсол',
                                'query_param'=>'safesoul_activity'
                            ]);
                            $currentWeek->safeSouls()->save($safeSoul);
                            $currentWeek->increment('points', ConstantValues::safesoul_activity_points);
                            $currentWeek->increment('total_points', ConstantValues::safesoul_activity_points);
                        }else{
                            Log::info('safesoul activity update error, no such account ');
                        }
                    }
                }
            }else{
                Log::info('safesoul activity update error: ' . $response->status());
            }
        }catch (\Exception $e){
            Log::info('safesoul activity update error: ' . $e );
        }

    }

    public function getAchieves()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ux9AgzrHauOq6sF8kZSetP4eCAB7z2OoxLOQP1fpQCZflMrPBQdUVdQehCdGDrPZ',
            ])->get($this->safesoul . 'api/airdrop/user/achieve');

            if ($response->successful()) {
                $achievements = $response->json();

                if (isset($achievements)) {
                    foreach ($achievements as $wallet => $achievement) {
                        $account = Account::where('wallet', $wallet)->first();

                        if ($account && isset($achievement['achieves'])) {
                            foreach ($achievement['achieves'] as $key => $value) {
                                $currentWeek = Week::getCurrentWeekForAccount($account);

//                                $safeSoul = $account->safeSouls()->where('query_param', $value)->first();
                                $safeSoul =  SafeSoul::where('query_param', $value)
                                    ->where('account_id', $account->id)->first();

                                if (!$safeSoul) {
                                    $safeSoul = new SafeSoul([
                                        'account_id' => $account->id,
//                                'week_id' => $currentWeek->id,
                                        'points' => ConstantValues::safesoul_achievement_points,
                                        'comment' => 'ачивки' . $value,
                                        'query_param' => $value
                                    ]);
                                    $currentWeek->safeSouls()->save($safeSoul);
                                    $currentWeek->increment('points', ConstantValues::safesoul_achievement_points);
                                    $currentWeek->increment('total_points', ConstantValues::safesoul_achievement_points);
                                }
                            }
                        }
                    }
                }
            } else {
                Log::info('safesoul achieve update error. Status code: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('An error occurred during safesoul achieve update: ' . $e->getMessage());
        }
    }

    public function getInvites()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ux9AgzrHauOq6sF8kZSetP4eCAB7z2OoxLOQP1fpQCZflMrPBQdUVdQehCdGDrPZ',
            ])->get($this->safesoul . 'api/airdrop/invites');

            if ($response->successful()) {
                $invites = $response->json();

                if(isset($invites)){
                    foreach ($invites as $wallet=>$invite){
                        $account = Account::where('wallet', $wallet)->first();
                        if ($account && isset($invite['invites'])) {
                            foreach ($invite['invites'] as $key => $value) {
//                                dd($invite['invites']);
                                $currentWeek = Week::getCurrentWeekForAccount($account);

//                                $safeSoul = $account->safeSouls()->where('query_param', $key)->first();
                                $safeSoul =  SafeSoul::where('query_param', $key)
                                    ->where('account_id', $account->id)->first();

                                $lux = Str::contains($key, ['_2k_', '_10k_', '_50k_']);
                                if (!$safeSoul && !$lux) {
                                    $safeSoul = new SafeSoul([
                                        'account_id' => $account->id,
//                                'week_id' => $currentWeek->id,
                                        'claim_points' => ConstantValues::safesoul_invited_person,
                                        'comment' => 'Инвайт человека, wallet = '. $value,
                                        'query_param' => $key
                                    ]);

                                    $currentWeek->safeSouls()->save($safeSoul);
                                    $currentWeek->increment('claim_points', ConstantValues::safesoul_invited_person);

                                }elseif (!$safeSoul && $lux){
                                    if(Str::contains($key, '_2k_')){
                                        $safeSoul = new SafeSoul([
                                            'account_id' => $account->id,
//                                'week_id' => $currentWeek->id,
                                            'claim_points' => ConstantValues::safesoul_invited_2k,
                                            'comment' => 'Инвайт человека c 2k ,wallet = '. $value,
                                            'query_param' => $key
                                        ]);

                                        $currentWeek->safeSouls()->save($safeSoul);
                                        $currentWeek->increment('claim_points', ConstantValues::safesoul_invited_2k);

                                    }elseif (Str::contains($value, '_10k_')){
                                        $safeSoul = new SafeSoul([
                                            'account_id' => $account->id,
//                                'week_id' => $currentWeek->id,
                                            'claim_points' => ConstantValues::safesoul_invited_10k,
                                            'comment' => 'Инвайт человека c 10k, wallet' . $value,
                                            'query_param' => $key
                                        ]);
                                        $currentWeek->safeSouls()->save($safeSoul);
                                        $currentWeek->increment('claim_points', ConstantValues::safesoul_invited_10k);
                                    }else{
                                        $safeSoul = new SafeSoul([
                                            'account_id' => $account->id,
//                                'week_id' => $currentWeek->id,
                                            'claim_points' => ConstantValues::safesoul_invited_50k,
                                            'comment' => 'Инвайт человека c 50k, wallet'. $value,
                                            'query_param' => $key
                                        ]);
                                        $currentWeek->safeSouls()->save($safeSoul);
                                        $currentWeek->increment('claim_points', ConstantValues::safesoul_invited_50k);
//                                        $account->safeSouls()->save($safeSoul);
                                    }
                                }
                            }
                        }
                    }
                }
            }else {
                Log::info('safesoul invites update error. Status code: ' . $response->status());
            }
        }catch (\Exception $e){
            Log::error('An error occurred during safesoul invites update: ' . $e->getMessage());
        }

    }

    public function each100ReportsUpdate()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ux9AgzrHauOq6sF8kZSetP4eCAB7z2OoxLOQP1fpQCZflMrPBQdUVdQehCdGDrPZ',
            ])->get( $this->safesoul .'api/airdrop/user/reports');
            if($response->status() ==200){
                $votes = $response->json();

                foreach ($votes as $voteData) {
                    $wallet = $voteData['wallet'];
                    $accountId = $this->getAccountIdByWallet($wallet);

                    $vts = $voteData['votes'];
                    foreach ($vts as $item){
                        // Check if the votes have been processed for this wallet
                        if (!$this->areVotesProcessed($accountId, $item)) {

                            // Calculate points based on the number of votes (0.7 points per vote)
                            $pointsToAdd = min(count($voteData['votes']) * ConstantValues::safesoul_reports_points, ConstantValues::safesoul_reports_limit);

                            if ($this->hasReachedWeeklyLimit($accountId, $pointsToAdd)) {
                                continue; // Skip processing further votes for this wallet
                            }

                            // Update points in safe_souls table
                            $this->updateSafeSoulPoints($accountId, ConstantValues::safesoul_reports_points, $item);

                        }
                    }
                }
            }else{
                Log::info('safesoul reports update error: ' . $response->status() );
            }
        }catch (\Exception $e ){
            Log::info('safesoul reports update error: ' . $e );
        }

    }

    public function updatePointsForGitcoin()
    {
        $accounts = Account::cursor();

        foreach ($accounts as $account){
            if($account->gitcoin_score > 0){
                $currentWeek = Week::getCurrentWeekForAccount($account);

                $value = 'gitcoin_score';
                $safeSoul =  SafeSoul::where('query_param', $value)
                    ->where('account_id', $account->id)
                    ->first();
                if(!$safeSoul){
                    $safeSoul = new SafeSoul([
                        'account_id' => $account->id,
                        'points' => ConstantValues::safesoul_gitcoin_score,
                        'comment' => $value,
                        'query_param' => $value
                    ]);
                    $currentWeek->safeSouls()->save($safeSoul);
                    $currentWeek->increment('points', ConstantValues::safesoul_gitcoin_score);
                    $currentWeek->increment('total_points', ConstantValues::safesoul_gitcoin_score);
                }
            }
        }
    }

    public function updateGitcoinPointsForGitcoinTwitterFollowing()
    {
        $client = new \GuzzleHttp\Client();
//        $accounts = Account::whereIn('twitter_id', ['2567044084', '820166162'])->get();
        $accounts = Account::whereNotNull('twitter_id')->cursor();

        foreach ($accounts as $account){
            $followingRestIds = $this->getTwitterFollowings($client, 'followings', $account->twitter_id);

            $key = 'follow_gitcoin_passport';
            $safeSoul =  SafeSoul::where('query_param', $key)
                ->where('account_id', $account->id)
                ->first();
            if(in_array(ConstantValues::gitcoin_passport, $followingRestIds) && !$safeSoul){
                $currentWeek = Week::getCurrentWeekForAccount($account);


                $safeSoul = new SafeSoul([
                    'account_id' => $account->id,
                    'claim_points' => ConstantValues::follow_gitcoin_passport,
                    'comment' => 'Follow gitcoin passport' ,
                    'query_param' => $key
                ]);
                $currentWeek->safeSouls()->save($safeSoul);
                $currentWeek->increment('claim_points', ConstantValues::follow_gitcoin_passport);
            }
        }
    }

    private function getAccountIdByWallet($wallet)
    {
        $account = Account::where('wallet', $wallet)->first();

        return $account ? $account->id : null;
    }

    private function areVotesProcessed($accountId, $item)
    {
        // Check if all votes are already processed for this account

          return  SafeSoul::where('account_id', $accountId)
                ->where('query_param', $item)
                ->whereBetween('created_at', [now()->subDays(7), now()])
                ->count() === 1;

    }

    private function hasReachedWeeklyLimit($accountId, $pointsToAdd)
    {

        $currentWeek = Week::getCurrentWeekForAccount(Account::findOrFail($accountId));

        // Sum claim_points for the current week
        $weeklyTotal = SafeSoul::where('account_id', $accountId)
            ->where('week_id', $currentWeek->id) // Ensure we're looking at the current week
            ->sum('claim_points');

        // Check if adding the new points would exceed the weekly limit
        return ($weeklyTotal + $pointsToAdd) > ConstantValues::safesoul_reports_limit;
    }

    private function updateSafeSoulPoints($accountId, $pointsToAdd, $vote)
    {

        $currentWeek = Week::getCurrentWeekForAccount(Account::findOrFail($accountId));


            $safesoul = SafeSoul::where([
                'account_id' => $accountId,
                'query_param' => $vote,

            ])->first();

            if (!$safesoul) {

                $safesoul = SafeSoul::create([
                    'account_id' => $accountId,
                    'query_param' => $vote,
                    'claim_points' => $pointsToAdd,
                    'comment' => 'за каждые 100 отправленных репортов',
                ]);
                $currentWeek->safeSouls()->save($safesoul);
                $currentWeek->increment('claim_points', $pointsToAdd);
            }
    }

    private function downloadTwitterAvatar($result): ?string
    {
        $TWITTER_AVATAR_PATH = 'twitter/avatars';

        try {

            if (isset($result)) {

//                $url = str_replace('_normal', '', $result);
                $url=$result;

                $contents = file_get_contents($url);

                $filename = $TWITTER_AVATAR_PATH. '/'. md5($url) . '.' . pathinfo($url, PATHINFO_EXTENSION);

                // Save the file to the public disk
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

    private function checkRole($discordId)
    {
        $scriptPath = base_path('./node/discord/role/checkRole.js');

//        dd($discordId);
        $process = new Process(['node', $scriptPath, $discordId]);
        $process->run();

// Check if the process was successful
        if ($process->isSuccessful()) {
            // Get the output of the script
            sleep(1);
            $output = $process->getOutput();


            $result = json_decode($output, true);

            if ($result['state'] === 'success') {

                $data = $result['data'];

                return $data;

            } else {

                $errorMessage = $result['data'];
                return null;

            }
        } else {
            // Handle the case when the process fails
            $exitCode = $process->getExitCode();
            $errorOutput = $process->getErrorOutput();
            Log::info($process->getErrorOutput());
            return null;
        }
    }

    private function getTwitterFollowings($client, $type, $twitterId)
    {
        $restIds = [];
        $next_cursor = null;

        do {
            try {

                $url = "https://twitter288.p.rapidapi.com/user/{$type}/ids?id=$twitterId&count=5000";
                if ($next_cursor !== null) {
                    sleep(1);
                    $url .= "&cursor=$next_cursor";
                }

                $response = $client->request('GET', $url, [
                    'headers' => [
                        'X-RapidAPI-Host' => 'twitter288.p.rapidapi.com',
                        'X-RapidAPI-Key' => '85c0d2d5d9msh8188cd5292dcd82p1e4a24jsn97b344b037a2'
                    ],
                ]);

                $responseBody = json_decode($response->getBody(), true);

                if (isset($responseBody['error'])) {

                    $this->error("Error from API: " . $responseBody['error']);
                    // Check for rate limiting or other headers
                    $retryAfter = $response->getHeader('Retry-After');
                    if ($retryAfter) {
                        $this->info("Retry after: " . $retryAfter[0]);
                        sleep($retryAfter[0]);
                    } else {
                        break; // Exit the loop if there is an error without a retry header
                    }
                }

                if(array_key_exists('ids', $responseBody)){
                    foreach ($responseBody['ids'] as $follower) {
                        $restIds[] = $follower;
                    }
                }
                $next_cursor = isset($responseBody['next_cursor_str']) && ($responseBody['next_cursor_str'] !== '0') ? $responseBody['next_cursor'] : null;



            }catch (\GuzzleHttp\Exception\GuzzleException $e){
                $this->error("An error occurred while fetching data: " . $e->getMessage());
                // Optionally, break or continue depending on how you want to handle failures
                break;
            }

        }while ($next_cursor);

        return $restIds;
    }
}
