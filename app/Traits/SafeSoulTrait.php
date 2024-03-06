<?php
namespace App\Traits;

use App\ConstantValues;
use App\Models\Account;
use App\Models\DiscordRole;
use App\Models\SafeSoul;
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

    public function getAccountsUpdate()
    {

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ux9AgzrHauOq6sF8kZSetP4eCAB7z2OoxLOQP1fpQCZflMrPBQdUVdQehCdGDrPZ',
            ])->get( $this->safesoul .'api/airdrop/user/info');

            if($response->status() ==200){

                $data = $response->json();
                if(isset($data)){
                    foreach ($data as $item){

                        $account = Account::where('wallet', $item['wallet'])->first();
                        if($account){
                            usleep(5000);
                            $account->update([
                                'twitter_username'=>$item['twitter_username'],
                                'twitter_name'=>$item['twitter_name'],
                                'twitter_avatar'=> isset($item['twitter_avatar']) ? $this->downloadTwitterAvatar($item['twitter_avatar']) : null,
                                'twitter_id'=>$item['twitter_id'],
                                'role'=>$item['role'],
                                'discord_id'=>$item['discord_id']
                            ]);
                            $account->save();
                            if(!is_null($account->role) && $account->wallet == '0xf7bf7651733db500ff13321c2d39e4beaced70bd'){
                                $this->info($item['role']);
                                $this->info($account->wallet);
                            }

                        }else{
                            $account = new Account([
                                'wallet'=> $item['wallet'],
                                'twitter_username'=>$item['twitter_username'],
                                'twitter_name'=>$item['twitter_name'],
                                'twitter_avatar'=>isset($item['twitter_avatar']) ? $this->downloadTwitterAvatar($item['twitter_avatar']) : null,
                                'twitter_id'=>$item['twitter_id'],
                                'role'=>$item['role'],
                                'auth_id'=>$item['auth_id'],
                                'discord_id'=>$item['discord_id']
                            ]);
                            $account->save();

                        }
                    }
                }
                return $response->json(['ok'=>'sdsfd'],200);
            }else{
                Log::info('getAccountsUpdate error: '. $response->status());
            }
        } catch (\Exception $e){
            Log::info('getAccountsUpdate error: '. $e);
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
                            $safeSoul = new SafeSoul([
                                'points' => ConstantValues::safesoul_activity_points,
                                'comment'=> 'активность в сейфсол',
                                'query_param'=>'safesoul_activity'
                            ]);
                            $account->safeSouls()->save($safeSoul);
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
                                $safeSoul = $account->safeSouls()->where('query_param', $value)->first();

                                if (!$safeSoul) {
                                    $safeSoul = new SafeSoul([
                                        'points' => ConstantValues::safesoul_achievement_points,
                                        'comment' => 'ачивки',
                                        'query_param' => $value
                                    ]);

                                    $account->safeSouls()->save($safeSoul);
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
                                $safeSoul = $account->safeSouls()->where('query_param', $key)->first();

                                $lux = Str::contains($key, ['_2k_', '_10k_', '_50k_']);
                                if (!$safeSoul && !$lux) {
                                    $safeSoul = new SafeSoul([
                                        'points' => ConstantValues::safesoul_invited_person,
                                        'comment' => 'Инвайт человека, wallet = '. $value,
                                        'query_param' => $key
                                    ]);

                                    $account->safeSouls()->save($safeSoul);
                                }elseif (!$safeSoul && $lux){
                                    if(Str::contains($key, '_2k_')){
                                        $safeSoul = new SafeSoul([
                                            'points' => ConstantValues::safesoul_invited_2k,
                                            'comment' => 'Инвайт человека c 2k ,wallet = '. $value,
                                            'query_param' => $key
                                        ]);
                                        $account->safeSouls()->save($safeSoul);
                                    }elseif (Str::contains($value, '_10k_')){
                                        $safeSoul = new SafeSoul([
                                            'points' => ConstantValues::safesoul_invited_10k,
                                            'comment' => 'Инвайт человека c 10k, wallet' . $value,
                                            'query_param' => $key
                                        ]);
                                        $account->safeSouls()->save($safeSoul);
                                    }else{
                                        $safeSoul = new SafeSoul([
                                            'points' => ConstantValues::safesoul_invited_50k,
                                            'comment' => 'Инвайт человека c 50k, wallet'. $value,
                                            'query_param' => $key
                                        ]);
                                        $account->safeSouls()->save($safeSoul);
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

//        return SafeSoul::where('account_id', $accountId)
//                ->whereIn('query_param', $votes)
////                ->where('processed', true)
//                ->whereBetween('created_at', [now()->subDays(7), now()])
//                ->count() === count($votes);
    }

    private function hasReachedWeeklyLimit($accountId, $pointsToAdd)
    {

        $weeklyTotal = SafeSoul::where('account_id', $accountId)
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->sum('points');

        // Check if adding the new points would exceed the weekly limit
        return $weeklyTotal > ConstantValues::safesoul_reports_limit;
    }

    private function updateSafeSoulPoints($accountId, $pointsToAdd, $vote)
    {
        // Logic to update points in the safe_souls table
        // You can use the SafeSoul model or DB facade for this
        // For example:

            SafeSoul::updateOrCreate(
                ['account_id' => $accountId, 'query_param'=>$vote],
                ['points' => $pointsToAdd,
                 'comment'=>'за каждые 100 отправленных репортов',

                ],
            );


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
        }

        return null;
    }
}
