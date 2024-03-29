<?php


namespace App\Traits;


use App\ConstantValues;
use App\Models\Account;
use App\Models\DigitalAnimal;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

trait DigitalAnimalsTrait
{
    public function getAnimals()
    {

        $accounts =  Account::whereNotNull('wallet')
            ->where('wallet', '!=', '')
            ->cursor();
//            ->whereIn('id', [7, 18])->get();
        foreach ($accounts as $account) {
            $process = new Process([
                'node',
                base_path('node/getAnimalsId.js'),
                $account->wallet,
            ]);

            $process->run();

            if ($process->isSuccessful()) {

                $data = json_decode($process->getOutput());
                if (is_array($data) && (count($data) > 0)) {
                    $animals = DigitalAnimal::where('query_param', 'like', 'token_%')
                        ->where('query_param', 'not like', 'token_owner_year_%')
                        ->where('account_id', $account->id)->get();

                    $existingQueryParams = $animals->pluck('query_param')->toArray();


                    $deletedAnimalsCount = 0;

                    // Check and delete animals that are not in the data
                    foreach ($animals as $animal) {

                        $numericToken = substr($animal->query_param, strlen('token_'));

                        if (!in_array($numericToken, $data)) {
                            var_dump('here');
                            $animal->delete();
                            $deletedAnimalsCount++;
                        }
                    }
//                    dd($deletedAnimalsCount);

                    if ($deletedAnimalsCount > 0) {
                        $currentWeek = Week::getCurrentWeekForAccount($account);
                        $pointsToDeduct = $deletedAnimalsCount * ConstantValues::animal_owner;
                        $currentWeek->decrement('points', $pointsToDeduct);
                    }

                    foreach ($data as $token) {
                        $this->info($token);
                        $queryParam = 'token_' . $token;
                        if (!in_array($queryParam, $existingQueryParams)) {

                            $currentWeek = Week::getCurrentWeekForAccount($account);

                            $newAnimal = new DigitalAnimal([
                                'account_id'=>$account->id,
                                'points' => ConstantValues::animal_owner,
                                'comment' => 'Владение животным: ' . $token,
                                'query_param' => $queryParam
                            ]);
                            $currentWeek->animals()->save($newAnimal);
                            $currentWeek->increment('points', ConstantValues::animal_owner);
                        }
                    }
                }
            } else {

                Log::info('Process error:' . $process->getErrorOutput());

            }
        }
    }

    public function lordPoints()
    {
        $ids = DB::table('digital_animals')
            ->where('query_param', 'like', 'token_%')
            ->distinct()
            ->pluck('account_id');

        $accounts = Account::whereIn('id', $ids)->get();

        foreach ($accounts as $account){

            $animalsCount = DigitalAnimal::where('query_param', 'like', 'token_%')->where('account_id', $account->id)->count();
//           $animalsCount = $account->animals()->where('query_param', 'like', 'token_%')->count();

//           $lord = $account->animals()->where('query_param', 'lord_20')->first();
           $lord = DigitalAnimal::where('query_param', 'lord_20')->where('account_id', $account->id)->first();
           $currentWeek = Week::getCurrentWeekForAccount($account);

           if($animalsCount >= 20 && !$lord){
               $newLord = new DigitalAnimal([
                   'account_id'=>$account->id,
                   'points' => ConstantValues::lord_20,
                   'comment' => 'Лорд (владение больше 20)',
                   'query_param' => 'lord_20'
               ]);
//               $account->animals()->save($newLord);
               $currentWeek->animals()->save($newLord);
               $currentWeek->increment('points', ConstantValues::lord_20);

           }elseif($animalsCount >= 20 && $lord){
               continue;
           }elseif($animalsCount < 20 && $lord){
               $newLord = new DigitalAnimal([
                   'account_id'=>$account->id,
                   'points' => -ConstantValues::lord_20,
                   'comment' => 'Лорд (владение больше 20)',
                   'query_param' => 'not_lord_20'
               ]);
//               $account->animals()->save($newLord);
               $currentWeek->animals()->save($newLord);
               $currentWeek->increment('points', -ConstantValues::lord_20);
           }
        }

    }

    public function getDiscordRole()
    {
        $accounts = Account::whereNotNull('discord_id')->get();

        foreach ($accounts as $account){
            $process = new Process([
                'node',
                base_path('node/getRoleDiscord.js'),
                $account->discord_id,
            ]);
            $currentWeek = Week::getCurrentWeekForAccount($account);

            $process->run();
            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput());
                if($data->state === 'success' && !is_null($data->data)){
                    if(is_array($data->data)){
                        $roles = $data->data;
                        //maybe issuees here
//                        $animals = $account->animals()->where('query_param', 'like', 'discord_%')->get();
//
//                        $existingQueryParams = $animals->pluck('query_param')->toArray();

                        $existingQueryParams = DigitalAnimal::where('query_param', 'like', 'discord_%')
                            ->where('account_id', $account->id)
                            ->pluck('query_param')
                            ->toArray();

//                        if(!empty($animals)){
//                            foreach ($animals as $animal) {
//
//                                if (!in_array($animal->query_param, $roles)) {
//                                    $animal->delete();
//                                }
//                            }
//                        }


                        foreach ($roles as $role){
                            if (!in_array($role, $existingQueryParams)) {

                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => ConstantValues::animal_discord_role_points,
                                    'comment' => 'Подключенные роли в дискорде '. $role,
                                    'query_param' => $role
                                ]);
//                                $account->animals()->save($newAnimal);
                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', ConstantValues::animal_discord_role_points);
                            }
                        }

                    }
                }
            }else{
                Log::info('Process error getDiscordRole function:' . $process->getErrorOutput());
            }
        }
    }

    public function getTokenOwnerAboutYear()
    {
        $account_ids = DB::table('digital_animals')
            ->where('query_param', 'like', 'token_%')
            ->distinct()
            ->pluck('account_id');

        $tokens = DB::table('digital_animals')
            ->where('query_param', 'like', 'token_%')
            ->distinct()
            ->pluck('query_param');
        $tokenNumbers = $tokens->map(function ($token) {
            return substr($token, 6); // Remove the first 6 characters: "token_"
        })->toArray();

        $accounts = Account::whereIn('id', $account_ids)->cursor();
//        dd($accounts);

        foreach ($accounts as $account){
            $currentWeek = Week::getCurrentWeekForAccount($account);

            foreach ($tokenNumbers as $token){
                $process = new Process([
                    'node',
                    base_path('node/getTokenHistory.js'),
                    $token,
                    $account->wallet,
                ]);

                $process->run();
                if ($process->isSuccessful()) {

                    $existingQueryParams = DigitalAnimal::where('query_param', 'like', 'token_owner_year_%')
                    ->where('account_id', $account->id)
                        ->pluck('query_param')
                        ->toArray();
//                    $existingQueryParams = $animals->pluck('query_param')->toArray();

                    $data = json_decode($process->getOutput());
                    if($data->state === 'success' && !is_null($data->data)){

                        $queryParam = 'token_owner_year_' . $token;
                        $this->info($data->data);
                        if (!in_array($queryParam, $existingQueryParams)) {
                            $newAnimal = new DigitalAnimal([
                                'account_id'=>$account->id,
                                'points' => ConstantValues::animal_long_range_owner_points,
                                'comment' => $data->data,
                                'query_param' => $queryParam
                            ]);
//                            $account->animals()->save($newAnimal);
                            $currentWeek->animals()->save($newAnimal);
                            $currentWeek->increment('points', ConstantValues::animal_long_range_owner_points);
                        }

                    }elseif($data->state === 'error' && !is_null($data->data)){
                        continue;
                    }
                }
            }
        }
    }

    public function getCommitedPassOwners()
    {
        $accounts =  Account::whereNotNull('wallet')
            ->where('wallet', '!=', '')
            ->cursor();

        foreach ($accounts as $account) {
            $currentWeek = Week::getCurrentWeekForAccount($account);

            $process = new Process([
                'node',
                base_path('node/checkCommitedOwnershipNft.js'),
                $account->wallet,
            ]);
            $process->run();
            if (!$process->isSuccessful()) {
                // Handle error or continue to the next iteration
                continue;
            }

            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput());
                var_dump($data->totalOwned);
                $existingPass = DigitalAnimal::where('query_param', 'like', 'committed_owner_%')
                    ->where('account_id', $account->id)->first();
                if(isset($data->totalOwned) && ($data->totalOwned > 0) || isset($existingPass)){
                    $this->info($account->wallet . ' '. $data->totalOwned);
                    if($existingPass){
                        $countPass = substr($existingPass->query_param, strlen('committed_owner_'));
                        if((int)$countPass == (int)$data->totalOwned ){
                            continue;
                        }elseif ((int)$data->totalOwned >(int)$countPass){
                            $points = ((int)$data->totalOwned -(int) $countPass) * ConstantValues::committed_pass_points;
                            $newAnimal = new DigitalAnimal([
                                'account_id'=>$account->id,
                                'points' => $points,
                                'comment' => $data->message,
                                'query_param' => 'committed_owner_' . $data->totalOwned
                            ]);
//                            $account->animals()->save($newAnimal);
                            $currentWeek->animals()->save($newAnimal);
                            $currentWeek->increment('points', $points);
                            $existingPass->update(['query_param'=>null]);
                        }elseif ((int)$countPass > (int)$data->totalOwned){
                            if((int)$data->totalOwned == 0){
                                $points = $existingPass->points;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => -$points,
                                    'comment' => $data->message,
                                    'query_param' => null
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', -$points);
                                $existingPass->update(['query_param'=>null]);
                            }elseif ((int)$data->totalOwned >0){
                                $points = ((int)$data->totalOwned - (int) $countPass) * ConstantValues::committed_pass_points;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => $points,
                                    'comment' => $data->message,
                                    'query_param' => 'committed_owner_' . $data->totalOwned
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', $points);
                                $existingPass->update(['query_param'=>null]);
                            }
                        }

                    }else{
                        $newAnimal = new DigitalAnimal([
                            'account_id'=>$account->id,
                            'points' => (int)$data->totalOwned * ConstantValues::committed_pass_points,
                            'comment' => $data->message,
                            'query_param' => 'committed_owner_' . $data->totalOwned
                        ]);
//                            $account->animals()->save($newAnimal);
                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', ConstantValues::committed_pass_points);
                    }
                }

            }
        }
    }

    public function getSoulbornePass()
    {
        $accounts =  Account::whereNotNull('wallet')
            ->where('wallet', '!=', '')
            ->cursor();

        foreach ($accounts as $account) {
            $currentWeek = Week::getCurrentWeekForAccount($account);

            $process = new Process([
                'node',
                base_path('node/checkSoulborneOwnershipNFT.js'),
                $account->wallet,
            ]);
            $process->run();
            if (!$process->isSuccessful()) {
                // Handle error or continue to the next iteration
                continue;
            }

            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput());
                var_dump($data->totalOwned);
                $existingPass = DigitalAnimal::where('query_param', 'like', 'soulborne_owner_%')
                    ->where('account_id', $account->id)->first();
                if(isset($data->totalOwned) && ($data->totalOwned > 0) || isset($existingPass)){
                    $this->info($account->wallet . ' '. $data->totalOwned);
                    if($existingPass){
                        $countPass = substr($existingPass->query_param, strlen('soulborne_owner_'));
                        if((int)$countPass == (int)$data->totalOwned ){
                            continue;
                        }elseif ((int)$data->totalOwned >(int)$countPass){
                            $points = ((int)$data->totalOwned -(int) $countPass) * ConstantValues::soulborne_pass_points;
                            $newAnimal = new DigitalAnimal([
                                'account_id'=>$account->id,
                                'points' => $points,
                                'comment' => $data->message,
                                'query_param' => 'soulborne_owner_' . $data->totalOwned
                            ]);
//                            $account->animals()->save($newAnimal);
                            $currentWeek->animals()->save($newAnimal);
                            $currentWeek->increment('points', $points);
                            $existingPass->update(['query_param'=>null]);
                        }elseif ((int)$countPass > (int)$data->totalOwned){
                            if((int)$data->totalOwned == 0){
                                $points = $existingPass->points;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => -$points,
                                    'comment' => $data->message,
                                    'query_param' => null
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', -$points);
                                $existingPass->update(['query_param'=>null]);
                            }elseif ((int)$data->totalOwned >0){
                                $points = ((int)$data->totalOwned - (int) $countPass) * ConstantValues::soulborne_pass_points;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => $points,
                                    'comment' => $data->message,
                                    'query_param' => 'soulborne_owner_' . $data->totalOwned
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', $points);
                                $existingPass->update(['query_param'=>null]);
                            }
                        }

                    }else{
                        $newAnimal = new DigitalAnimal([
                            'account_id'=>$account->id,
                            'points' => (int)$data->totalOwned * ConstantValues::soulborne_pass_points,
                            'comment' => $data->message,
                            'query_param' => 'soulborne_owner_' . $data->totalOwned
                        ]);
//                            $account->animals()->save($newAnimal);
                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', ConstantValues::soulborne_pass_points);
                    }
                }

            }
        }
    }

    public function getSoulReaperPass()
    {
        $accounts =  Account::whereNotNull('wallet')
            ->where('wallet', '!=', '')
            ->cursor();

        foreach ($accounts as $account) {
            $currentWeek = Week::getCurrentWeekForAccount($account);

            $process = new Process([
                'node',
                base_path('node/checkSoulReaperOwnershipNFT.js'),
                $account->wallet,
            ]);
            $process->run();
            if (!$process->isSuccessful()) {
                // Handle error or continue to the next iteration
                continue;
            }

            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput());
                var_dump($data->totalOwned);
                $existingPass = DigitalAnimal::where('query_param', 'like', 'soulreaper_owner_%')
                    ->where('account_id', $account->id)->first();
                if(isset($data->totalOwned) && ($data->totalOwned > 0) || isset($existingPass)){
                    $this->info($account->wallet . ' '. $data->totalOwned);
                    if($existingPass){
                        $countPass = substr($existingPass->query_param, strlen('soulreaper_owner_'));
                        if((int)$countPass == (int)$data->totalOwned ){
                            continue;
                        }elseif ((int)$data->totalOwned >(int)$countPass){
                            $points = ((int)$data->totalOwned -(int) $countPass) * ConstantValues::soulreaper_pass_points;
                            $newAnimal = new DigitalAnimal([
                                'account_id'=>$account->id,
                                'points' => $points,
                                'comment' => $data->message,
                                'query_param' => 'soulreaper_owner_' . $data->totalOwned
                            ]);
//                            $account->animals()->save($newAnimal);
                            $currentWeek->animals()->save($newAnimal);
                            $currentWeek->increment('points', $points);
                            $existingPass->update(['query_param'=>null]);
                        }elseif ((int)$countPass > (int)$data->totalOwned){
                            if((int)$data->totalOwned == 0){
                                $points = $existingPass->points;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => -$points,
                                    'comment' => $data->message,
                                    'query_param' => null
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', -$points);
                                $existingPass->update(['query_param'=>null]);
                            }elseif ((int)$data->totalOwned >0){
                                $points = ((int)$data->totalOwned - (int) $countPass) * ConstantValues::soulreaper_pass_points;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => $points,
                                    'comment' => $data->message,
                                    'query_param' => 'soulreaper_owner_' . $data->totalOwned
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', $points);
                                $existingPass->update(['query_param'=>null]);
                            }
                        }

                    }else{
                        $newAnimal = new DigitalAnimal([
                            'account_id'=>$account->id,
                            'points' => (int)$data->totalOwned * ConstantValues::soulreaper_pass_points,
                            'comment' => $data->message,
                            'query_param' => 'soulreaper_owner_' . $data->totalOwned
                        ]);
//                            $account->animals()->save($newAnimal);
                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', ConstantValues::soulreaper_pass_points);
                    }
                }

            }
        }
    }

    public function getLordOfTheReapersPass()
    {
        $accounts =  Account::whereNotNull('wallet')
            ->where('wallet', '!=', '')
            ->cursor();

        foreach ($accounts as $account) {
            $currentWeek = Week::getCurrentWeekForAccount($account);

            $process = new Process([
                'node',
                base_path('node/checkLordOfTheReapersOwnershipNFT.js'),
                $account->wallet,
            ]);
            $process->run();
            if (!$process->isSuccessful()) {
                // Handle error or continue to the next iteration
                continue;
            }

            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput());
                var_dump($data->totalOwned);
                $existingPass = DigitalAnimal::where('query_param', 'like', 'lord_reaper_owner_%')
                    ->where('account_id', $account->id)->first();
                if(isset($data->totalOwned) && ($data->totalOwned > 0) || isset($existingPass)){
                    $this->info($account->wallet . ' '. $data->totalOwned);
                    if($existingPass){
                        $countPass = substr($existingPass->query_param, strlen('lord_reaper_owner_'));
                        if((int)$countPass == (int)$data->totalOwned ){
                            continue;
                        }elseif ((int)$data->totalOwned >(int)$countPass){
                            $points = ((int)$data->totalOwned -(int) $countPass) * ConstantValues::lord_of_the_reapers;
                            $newAnimal = new DigitalAnimal([
                                'account_id'=>$account->id,
                                'points' => $points,
                                'comment' => $data->message,
                                'query_param' => 'lord_reaper_owner_' . $data->totalOwned
                            ]);
//                            $account->animals()->save($newAnimal);
                            $currentWeek->animals()->save($newAnimal);
                            $currentWeek->increment('points', $points);
                            $existingPass->update(['query_param'=>null]);
                        }elseif ((int)$countPass > (int)$data->totalOwned){
                            if((int)$data->totalOwned == 0){
                                $points = $existingPass->points;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => -$points,
                                    'comment' => $data->message,
                                    'query_param' => null
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', -$points);
                                $existingPass->update(['query_param'=>null]);
                            }elseif ((int)$data->totalOwned >0){
                                $points = ((int)$data->totalOwned - (int) $countPass) * ConstantValues::lord_of_the_reapers;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => $points,
                                    'comment' => $data->message,
                                    'query_param' => 'lord_reaper_owner_' . $data->totalOwned
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', $points);
                                $existingPass->update(['query_param'=>null]);
                            }
                        }

                    }else{
                        $newAnimal = new DigitalAnimal([
                            'account_id'=>$account->id,
                            'points' => (int)$data->totalOwned * ConstantValues::lord_of_the_reapers,
                            'comment' => $data->message,
                            'query_param' => 'lord_reaper_owner_' . $data->totalOwned
                        ]);
//                            $account->animals()->save($newAnimal);
                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', ConstantValues::lord_of_the_reapers);
                    }
                }

            }
        }
    }

    public function animalsOwnerForMetaverse()
    {
        $accounts =  Account::whereNotNull('wallet')
            ->where('wallet', '!=', '')
            ->cursor();

        foreach ($accounts as $account) {
            $currentWeek = Week::getCurrentWeekForAccount($account);

            $process = new Process([
                'node',
                base_path('node/checkDAAirdropOwnershipNFT.js'),
                $account->wallet,
            ]);
            $process->run();
            if (!$process->isSuccessful()) {
                // Handle error or continue to the next iteration
                continue;
            }

            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput());
                var_dump($data->totalOwned);
                $existingPass = DigitalAnimal::where('query_param', 'like', 'metaverse_owner_%')
                                                ->where('account_id', $account->id)
                                                ->first();

                if(isset($data->totalOwned) && ($data->totalOwned > 0) || isset($existingPass)){
                    $this->info($account->wallet . ' '. $data->totalOwned);
                    if($existingPass){
                        $countPass = substr($existingPass->query_param, strlen('metaverse_owner_'));
                        if((int)$countPass == (int)$data->totalOwned ){
                            continue;
                        }elseif ((int)$data->totalOwned >(int)$countPass){
                            $points = ((int)$data->totalOwned -(int) $countPass) * ConstantValues::metaverse_owner;
                            $newAnimal = new DigitalAnimal([
                                'account_id'=>$account->id,
                                'points' => $points,
                                'comment' => $data->message,
                                'query_param' => 'metaverse_owner_' . $data->totalOwned
                            ]);
//                            $account->animals()->save($newAnimal);
                            $currentWeek->animals()->save($newAnimal);
                            $currentWeek->increment('points', $points);
                            $existingPass->update(['query_param'=>null]);
                        }elseif ((int)$countPass > (int)$data->totalOwned){
                            if((int)$data->totalOwned == 0){
                                $points = $existingPass->points;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => -$points,
                                    'comment' => $data->message,
                                    'query_param' => null
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', -$points);
                                $existingPass->update(['query_param'=>null]);
                            }elseif ((int)$data->totalOwned >0){
                                $points = ((int)$data->totalOwned - (int) $countPass) * ConstantValues::metaverse_owner;
                                $newAnimal = new DigitalAnimal([
                                    'account_id'=>$account->id,
                                    'points' => $points,
                                    'comment' => $data->message,
                                    'query_param' => 'metaverse_owner_' . $data->totalOwned
                                ]);

                                $currentWeek->animals()->save($newAnimal);
                                $currentWeek->increment('points', $points);
                                $existingPass->update(['query_param'=>null]);
                            }
                        }

                    }else{
                        $newAnimal = new DigitalAnimal([
                            'account_id'=>$account->id,
                            'points' => (int)$data->totalOwned * ConstantValues::metaverse_owner,
                            'comment' => $data->message,
                            'query_param' => 'metaverse_owner_' . $data->totalOwned
                        ]);
//                            $account->animals()->save($newAnimal);
                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', ConstantValues::metaverse_owner);
                    }
                }

            }
        }
    }

    public function getOriginalMinter()
    {

//            $currentWeek = Week::getCurrentWeekForAccount($account);
        for($i = 1; $i<8889; $i++){
            $process = new Process([
                'node',
                base_path('node/getDigitalAnimalsOrigitalMinters.js'),
                $i,
            ]);
            $process->run();
            if (!$process->isSuccessful()) {

                continue;
            }

            if ($process->isSuccessful()) {
                $originalMinterOwner = json_decode($process->getOutput());

                $existingMinterParams = DigitalAnimal::where('query_param', 'like', 'minter_%')->pluck('query_param')->toArray();

                $queryParam = 'minter_' . $i;

                if($originalMinterOwner !== '0x0000000000000000000000000000000000000000' && !in_array($queryParam, $existingMinterParams)){
                    $account = Account::where('wallet', $originalMinterOwner)->first();
                    if($account) {
                        $currentWeek = Week::getCurrentWeekForAccount($account);
                        $newAnimal = new DigitalAnimal([
                            'account_id' => $account->id,
                            'points' => ConstantValues::original_minter,
                            'comment' => "da token $i original minter",
                            'query_param' => 'minter_' . $i
                        ]);

                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', ConstantValues::original_minter);
                    }

                }else{
                    continue;
                }
            }
        }
    }

}
