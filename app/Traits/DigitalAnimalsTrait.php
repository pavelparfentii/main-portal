<?php


namespace App\Traits;


use App\ConstantValues;
use App\Models\Account;
use App\Models\DigitalAnimal;
use App\Models\OriginalMinter;
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
        $accounts = Account::whereNotNull('wallet')->where('wallet', '!=', '')->get(); // Change cursor() to get() if feasible for chunking

        $chunkSize = 10; // Process in chunks of 10 accounts
        $accounts->chunk($chunkSize)->each(function ($chunkedAccounts) {
            $processes = [];

            foreach ($chunkedAccounts as $account) {
                // Prepare the command to run your Node.js script with the wallet address
                $command = [
                    'node',
                    base_path('node/getAnimalsId.js'),
                    $account->wallet
                ];

                // Create a new process
                $process = new Process($command);
                $process->setTimeout(360); // Optional: Set a timeout for long-running processes

                // Start the process
                $process->start();
                $processes[] = $process;
            }

            // Wait for all processes in this chunk to finish
            foreach ($processes as $process) {
                $process->wait(function ($type, $buffer) use ($process) {
                    if ($type === Process::ERR) {
                        var_dump('here');
                        Log::error("Process error: {$buffer}");
                    } else {
                        $this->handleProcessOutput($process, $buffer);
                    }
                });
            }
        });
    }

    protected function handleProcessOutput(Process $process, $buffer)
    {
        $data = json_decode($buffer, true);
        if (is_array($data) && count($data) > 0) {
            var_dump($data); // or implement further data handling logic here
        }
    }


//    public function getAnimals()
//    {
//
//        $accounts =  Account::whereNotNull('wallet')
//            ->where('wallet', '!=', '')
//            ->cursor();
////            ->whereIn('id', [7, 18])->get();
//        foreach ($accounts as $account) {
//            $process = new Process([
//                'node',
//                base_path('node/getAnimalsId.js'),
//                $account->wallet,
//            ]);
//            $process->setTimeout(3600);
//
//            $process->run();
//
//            if ($process->isSuccessful()) {
//                $currentWeek = Week::getCurrentWeekForAccount($account);
//                $data = json_decode($process->getOutput());
//                if (is_array($data) && (count($data) > 0)) {
//                    $animals = DigitalAnimal::where('query_param', 'like', 'token_%')
//                        ->where('query_param', 'not like', 'token_owner_year_%')
//                        ->where('account_id', $account->id)->get();
//
//                    $existingQueryParams = $animals->pluck('query_param')->toArray();
//
//
//                    $deletedAnimalsCount = 0;
//
//                    // Check and delete animals that are not in the data
//                    foreach ($animals as $animal) {
//
//                        $numericToken = substr($animal->query_param, strlen('token_'));
//
//                        if (!in_array($numericToken, $data)) {
//                            var_dump('here');
//                            $newAnimal = new DigitalAnimal([
//                                'account_id' => $account->id,
//                                'points' => ConstantValues::animal_owner,
//                                'comment' => "Token ID $numericToken token no more owned",
//                                'query_param' => null // or some indication that it's for a sold token
//                            ]);
//                            $animal->delete();
//                            $deletedAnimalsCount++;
//                        }
//                    }
////                    dd($deletedAnimalsCount);
//
//                    if ($deletedAnimalsCount > 0) {
//                        $currentWeek = Week::getCurrentWeekForAccount($account);
//                        $pointsToDeduct = $deletedAnimalsCount * ConstantValues::animal_owner;
//                        $currentWeek->decrement('points', $pointsToDeduct);
//                        $currentWeek->decrement('total_points', $pointsToDeduct);
//                    }
//
//                    foreach ($data as $token) {
//                        $this->info($token);
//                        $queryParam = 'token_' . $token;
//                        if (!in_array($queryParam, $existingQueryParams)) {
//
//                            $currentWeek = Week::getCurrentWeekForAccount($account);
//
//                            $newAnimal = new DigitalAnimal([
//                                'account_id'=>$account->id,
//                                'points' => ConstantValues::animal_owner,
//                                'comment' => 'Владение животным: ' . $token,
//                                'query_param' => $queryParam
//                            ]);
//                            $currentWeek->animals()->save($newAnimal);
//                            $currentWeek->increment('points', ConstantValues::animal_owner);
//                            $currentWeek->increment('total_points', ConstantValues::animal_owner);
//                        }
//                    }
//                }
//            } else {
//
//                Log::info('Process error:' . $process->getErrorOutput());
//
//            }
//        }
//    }

    public function lordPoints()
    {
        $ids = DB::table('digital_animals')
            ->where('query_param', 'like', 'token_%')
            ->where('query_param', 'not like', 'token_owner_year_%')
            ->distinct()
            ->pluck('account_id');

        $accounts = Account::whereIn('id', $ids)->get();

        foreach ($accounts as $account){

            $animalsCount = DigitalAnimal::where('query_param', 'like', 'token_%')
                ->where('query_param', 'not like', 'token_owner_year_%')
                ->where('account_id', $account->id)
                ->count();
//           $animalsCount = $account->animals()->where('query_param', 'like', 'token_%')->count();

//           $lord = $account->animals()->where('query_param', 'lord_20')->first();
           $lord = DigitalAnimal::where('query_param', 'lord_20')
               ->where('account_id', $account->id)
               ->first();

           $not_lord = DigitalAnimal::where('query_param', 'not_lord_20')
               ->where('account_id', $account->id)
               ->first();
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
               $currentWeek->increment('total_points', ConstantValues::lord_20);

               if($not_lord){
                   $not_lord->update(['query_param'=>null]);
               }


           }elseif($animalsCount >= 20 && $lord){
               continue;
           }elseif($animalsCount < 20 && $lord && !$not_lord){
               $newLord = new DigitalAnimal([
                   'account_id'=>$account->id,
                   'points' => -ConstantValues::lord_20,
                   'comment' => 'Лорд (владение больше 20)',
                   'query_param' => 'not_lord_20'
               ]);
//               $account->animals()->save($newLord);
               $currentWeek->animals()->save($newLord);
               $currentWeek->increment('points', -ConstantValues::lord_20);
               $currentWeek->increment('total_points', -ConstantValues::lord_20);
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
                                $currentWeek->increment('total_points', ConstantValues::animal_discord_role_points);
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
                            $currentWeek->increment('total_points', ConstantValues::animal_long_range_owner_points);
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
                            $currentWeek->increment('total_points', $points);
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
                                $currentWeek->increment('total_points', -$points);
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
                                $currentWeek->increment('total_points', $points);
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
                        $currentWeek->increment('points', (int)$data->totalOwned * ConstantValues::committed_pass_points);
                        $currentWeek->increment('total_points', (int)$data->totalOwned * ConstantValues::committed_pass_points);
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
                            $currentWeek->increment('total_points', $points);
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
                                $currentWeek->increment('total_points', -$points);
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
                                $currentWeek->increment('total_points', $points);
                                $existingPass->update(['query_param'=>null]);
                            }
                        }

                    }else{
                        $points = (int)$data->totalOwned * ConstantValues::soulborne_pass_points;
                        $newAnimal = new DigitalAnimal([
                            'account_id'=>$account->id,
                            'points' => $points,
                            'comment' => $data->message,
                            'query_param' => 'soulborne_owner_' . $data->totalOwned
                        ]);
//                            $account->animals()->save($newAnimal);
                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', $points);
                        $currentWeek->increment('total_points', $points);
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
                            $currentWeek->increment('total_points', $points);
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
                                $currentWeek->increment('total_points', $points);
                                $existingPass->update(['query_param'=>null]);
                            }
                        }

                    }else{
                        $points = (int)$data->totalOwned * ConstantValues::soulreaper_pass_points;
                        $newAnimal = new DigitalAnimal([
                            'account_id'=>$account->id,
                            'points' => $points,
                            'comment' => $data->message,
                            'query_param' => 'soulreaper_owner_' . $data->totalOwned
                        ]);
//                            $account->animals()->save($newAnimal);
                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', $points);
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
                            $currentWeek->increment('total_points', $points);
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
                                $currentWeek->increment('total_points', -$points);
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
                                $currentWeek->increment('total_points', $points);
                                $existingPass->update(['query_param'=>null]);
                            }
                        }

                    }else{
                        $points = (int)$data->totalOwned * ConstantValues::lord_of_the_reapers;
                        $newAnimal = new DigitalAnimal([
                            'account_id'=>$account->id,
                            'points' => $points,
                            'comment' => $data->message,
                            'query_param' => 'lord_reaper_owner_' . $data->totalOwned
                        ]);
//                            $account->animals()->save($newAnimal);
                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', $points);
                        $currentWeek->increment('total_points', $points);
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
                            $currentWeek->increment('total_points', $points);
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
                                $currentWeek->increment('total_points', -$points);
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
                                $currentWeek->increment('total_points', $points);
                                $existingPass->update(['query_param'=>null]);
                            }
                        }

                    }else{
                        $points = (int)$data->totalOwned * ConstantValues::metaverse_owner;
                        $newAnimal = new DigitalAnimal([
                            'account_id'=>$account->id,
                            'points' => $points,
                            'comment' => $data->message,
                            'query_param' => 'metaverse_owner_' . $data->totalOwned
                        ]);
//                            $account->animals()->save($newAnimal);
                        $currentWeek->animals()->save($newAnimal);
                        $currentWeek->increment('points', $points);
                        $currentWeek->increment('total_points', $points);
                    }
                }
            }
        }
    }

    public function getOriginalMinter()
    {

        $originalMinters = OriginalMinter::query()->get();

        foreach ($originalMinters as $minter) {
            $account = Account::where('wallet', $minter->wallet)->first();

            if($account){
                $existingMinterParams = DigitalAnimal::where('query_param', 'like', 'minter_%')->where('account_id', $account->id)->first();

                if (!$existingMinterParams) {
                    $points = ConstantValues::original_minter;
                    $currentWeek = Week::getCurrentWeekForAccount($account);
                    $newAnimal = new DigitalAnimal([
                        'account_id' => $account->id,
                        'points' => $points,
                        'comment' => "da token $minter->token original minter",
                        'query_param' => 'minter_' . $minter->token
                    ]);

                    $currentWeek->animals()->save($newAnimal);
                    $currentWeek->increment('points', $points);
                    $currentWeek->increment('total_points', $points);
                }
            }
        }

    }


//        if($originalMinterOwner !== '0x0000000000000000000000000000000000000000' && ){
//            $account = Account::where('wallet', $originalMinterOwner)->first();
//            if($account) {
//                $points = ConstantValues::original_minter;
//                $currentWeek = Week::getCurrentWeekForAccount($account);
//                $newAnimal = new DigitalAnimal([
//                    'account_id' => $account->id,
//                    'points' => $points,
//                    'comment' => "da token $i original minter",
//                    'query_param' => 'minter_' . $i
//                ]);
//
//                $currentWeek->animals()->save($newAnimal);
//                $currentWeek->increment('points', $points);
//                $currentWeek->increment('total_points', $points);
//            }
//
//        }else{
//            continue;
//        }
//
//
////            $currentWeek = Week::getCurrentWeekForAccount($account);
//        for($i = 1; $i<8889; $i++){
//            $process = new Process([
//                'node',
//                base_path('node/getDigitalAnimalsOrigitalMinters.js'),
//                $i,
//            ]);
//            $process->run();
//            if (!$process->isSuccessful()) {
//
//                continue;
//            }
//
//            if ($process->isSuccessful()) {
//                $originalMinterOwner = json_decode($process->getOutput());
//
//                $existingMinterParams = DigitalAnimal::where('query_param', 'like', 'minter_%')->pluck('query_param')->toArray();
//
//                $queryParam = 'minter_' . $i;
//
//                if($originalMinterOwner !== '0x0000000000000000000000000000000000000000' && !in_array($queryParam, $existingMinterParams)){
//                    $account = Account::where('wallet', $originalMinterOwner)->first();
//                    if($account) {
//                        $points = ConstantValues::original_minter;
//                        $currentWeek = Week::getCurrentWeekForAccount($account);
//                        $newAnimal = new DigitalAnimal([
//                            'account_id' => $account->id,
//                            'points' => $points,
//                            'comment' => "da token $i original minter",
//                            'query_param' => 'minter_' . $i
//                        ]);
//
//                        $currentWeek->animals()->save($newAnimal);
//                        $currentWeek->increment('points', $points);
//                        $currentWeek->increment('total_points', $points);
//                    }
//
//                }else{
//                    continue;
//                }
//            }
//        }
//    }

    public function getSaleTransactions()
    {
        $process = new Process([
            'node',
            base_path('node/fetchSaleEvents.js'),

        ]);

        $process->setTimeout(3600);
        $process->start();


        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                // Handle standard output
                $outputData = json_decode($data);
                if($outputData != null){
//                    var_dump($outputData);
                    $date = Carbon::parse($outputData->date);
                    if($date->isPast()){
                        continue;
                    }else{
                        $existingSaleParams = DigitalAnimal::where('query_param', 'like', 'bought_token_%')
                            ->pluck('query_param')
                            ->toArray();
                        $account = Account::where('wallet', strtolower($outputData->wallet) )->first();
                        $queryParam = 'bought_token_' . $outputData->token;
                        if($account && !in_array($queryParam, $existingSaleParams)) {
                            $currentWeek = Week::getCurrentWeekForAccount($account);
                            $points = ConstantValues::bought_animal;
                            $newAnimal = new DigitalAnimal([
                                'account_id' => $account->id,
                                'points' => $points,
                                'comment' => $outputData->message,
                                'query_param' => 'bought_token_' . $outputData->token
                            ]);

                            $currentWeek->animals()->save($newAnimal);
                            $currentWeek->increment('points', $points);
                            $currentWeek->increment('total_points', $points);
                        }
                    }
                }

            } else { // $process::ERR === $type
                Log::info('fetchSaleEvents script error :' . $data);
            }
        }
    }

    public function getMinterNeverSold()
    {

        $originalMinters = OriginalMinter::cursor();
        $neverSoldArray = [];
        foreach ($originalMinters as $minter){
            $process = new Process([
                'node',
                base_path('node/checkOriginalMinterAndNeverSold.js'),
                $minter->token,
                $minter->wallet
            ]);

            $process->setTimeout(9000);
            $process->start();
            $accountsData = [];
            foreach ($process as $type => $data) {
                if ($process::OUT === $type) {
                    $outputData = json_decode($data);
                    var_dump($outputData);

                    if ($outputData != null && !empty($outputData)) {
                        $wallet = $outputData->wallet;
                        $tokenId = $outputData->tokenId;
                        $message = $outputData->message;



                        if (!array_key_exists($wallet, $accountsData)) {
                            $accountsData[$wallet] = [
                                'tokens' => [],
                                'message' => ''
                            ];
                        }
                        $accountsData[$wallet]['tokens'] = $tokenId;
                        $accountsData[$wallet]['message'] .= $message . ' ;';
                        $neverSoldArray[] = $tokenId;
                        var_dump($neverSoldArray);
                    }
                }
            }

            $this->checkForAnyTokenOwnershipAndNeverSold($accountsData);
        }

//        $soldArray = OriginalMinter::whereNotIn('token', $neverSoldArray)->pluck('token')->toArray();
//
//        foreach ($soldArray as $token){
//            $this->decrementPointsForSoldToken($token);
//        }

    }

    private function checkForAnyTokenOwnershipAndNeverSold($accountsData)
    {
        foreach ($accountsData as $wallet => $info) {
            $account = Account::where('wallet', $wallet)->first();

            if ($account) {
                $queryParam = 'minter_never_sold';

                $animal = DigitalAnimal::where('query_param', 'minter_never_sold')
                    ->where('account_id', $account->id)->first();

                if(!$animal){
                    $this->awardPointsForNeverSoldToken($account, $info['message'], $queryParam);
                }

//                $animals = DigitalAnimal::where('query_param', 'like', 'minter_never_sold_%')
//                    ->where('account_id', $account->id)->get();
//
//                $existingTokens = $animals->pluck('query_param')->map(function ($queryParam) {
//                    return substr($queryParam, strrpos($queryParam, '_') + 1);
//                })->toArray();
//
//                $tokenId = $info['tokens'];
//
//                $queryParam = 'minter_never_sold_' . $tokenId;
//                if (!in_array($tokenId, $existingTokens)) {
//                    // Token was never sold and is not recorded in the database
//                    $this->awardPointsForNeverSoldToken($account, $info['message'], $queryParam);
//                }

            }
        }

    }

    private function awardPointsForNeverSoldToken($account, $message, $queryParam)
    {


        $points = ConstantValues::minter_never_sold;
        $currentWeek = Week::getCurrentWeekForAccount($account);
        $newAnimal = new DigitalAnimal([
            'account_id' => $account->id,
            'points' => $points,
            'comment' => $message,
            'query_param' => $queryParam
        ]);
        $newAnimal->save();
        $currentWeek->animals()->save($newAnimal);
        $currentWeek->increment('points', $points);
        $currentWeek->increment('total_points', $points);
    }

    private function decrementPointsForSoldToken($tokenId)
    {

        $digitalAnimal = DigitalAnimal::where('query_param', 'minter_never_sold_' . $tokenId)
            ->first();

        if($digitalAnimal){
            $account = Account::where('id', $digitalAnimal->account_id)->first();

            $points = -ConstantValues::minter_never_sold;
            $currentWeek = Week::getCurrentWeekForAccount($account);


                $digitalAnimal->delete(); // or you might want to keep a record and mark it as sold instead of deleting


            $newAnimal = new DigitalAnimal([
                'account_id' => $account->id,
                'points' => $points,
                'comment' => "Token ID $tokenId previously marked as never sold is now sold",
                'query_param' => null // or some indication that it's for a sold token
            ]);
            $newAnimal->save();
            $currentWeek->animals()->save($newAnimal);
            $currentWeek->increment('points', $points);
            $currentWeek->increment('total_points', $points);
        }


    }

}
