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
                    $animals = DigitalAnimal::where('query_param', 'like', 'token_%')->where('account_id', $account->id)->get();

                    $existingQueryParams = $animals->pluck('query_param')->toArray();

                    // Check and delete animals that are not in the data
                    foreach ($animals as $animal) {
                        // Remove 'token_' prefix for comparison
                        $numericToken = substr($animal->query_param, strlen('token_'));
                        if (!in_array($numericToken, $data)) {
                            $animal->delete();
                        }
                    }

                    foreach ($data as $token) {
                        $this->info($token);
                        $queryParam = 'token_' . $token;
                        if (!in_array($queryParam, $existingQueryParams)) {

//                            $currentWeek = Week::getCurrentWeekForAccount($account);
                            $currentWeek = Week::where('week_number', Carbon::now()->subWeek()->format('W-Y'))->Where('account_id', $account->id)->first();

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
//           $currentWeek = Week::getCurrentWeekForAccount($account);
            $currentWeek = Week::where('week_number', Carbon::now()->subWeek()->format('W-Y'))->Where('account_id', $account->id)->first();
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
//            $currentWeek = Week::getCurrentWeekForAccount($account);
            $currentWeek = Week::where('week_number', Carbon::now()->subWeek()->format('W-Y'))->Where('account_id', $account->id)->first();

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

                        if(!empty($animals)){
                            foreach ($animals as $animal) {

                                if (!in_array($animal->query_param, $roles)) {
                                    $animal->delete();
                                }
                            }
                        }


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
//            $currentWeek = Week::getCurrentWeekForAccount($account);
            $currentWeek = Week::where('week_number', Carbon::now()->subWeek()->format('W-Y'))->Where('account_id', $account->id)->first();
            foreach ($tokenNumbers as $token){
                $process = new Process([
                    'node',
                    base_path('node/getTokenHistory.js'),
                    $token,
                    $account->wallet,
                ]);

                $process->run();
                if ($process->isSuccessful()) {

                    $animals = $currentWeek->animals()->where('query_param', 'like', 'token_owner_year_%')->get();

                    $existingQueryParams = $animals->pluck('query_param')->toArray();

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
                            $currentWeek->increment('claim_points', ConstantValues::animal_long_range_owner_points);
                        }

                    }elseif($data->state === 'error' && !is_null($data->data)){
                        continue;

                    }
                }
            }

        }

    }

}
