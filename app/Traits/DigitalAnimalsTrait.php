<?php


namespace App\Traits;


use App\ConstantValues;
use App\Models\Account;
use App\Models\DigitalAnimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

trait DigitalAnimalsTrait
{
    public function getAnimals()
    {

        $accounts = Account::cursor();

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
                    $animals = $account->animals()->where('query_param', 'like', 'token_%')->get();

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
                            $newAnimal = new DigitalAnimal([
                                'points' => ConstantValues::animal_owner,
                                'comment' => 'Владение животным: ' . $token,
                                'query_param' => $queryParam
                            ]);
                            $account->animals()->save($newAnimal);
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
        $ids = DB::table('digital_animals')->where('query_param', 'like', 'token_%')->distinct()->pluck('account_id');

        $accounts = Account::whereIn('id', $ids)->get();

        foreach ($accounts as $account){
           $animalsCount = $account->animals()->where('query_param', 'like', 'token_%')->count();

           $lord = $account->animals()->where('query_param', 'lord_20')->first();

           if($animalsCount >= 20 && !$lord){
               $newLord = new DigitalAnimal([
                   'points' => ConstantValues::lord_20,
                   'comment' => 'Лорд (владение больше 20)',
                   'query_param' => 'lord_20'
               ]);
               $account->animals()->save($newLord);
           }elseif($animalsCount >= 20 && $lord){
               continue;
           }elseif($animalsCount < 20 && $lord){
               $newLord = new DigitalAnimal([
                   'points' => -ConstantValues::lord_20,
                   'comment' => 'Лорд (владение больше 20)',
                   'query_param' => 'not_lord_20'
               ]);
               $account->animals()->save($newLord);
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

            $process->run();
            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput());
                if($data->state === 'success' && !is_null($data->data)){
                    if(is_array($data->data)){
                        $roles = $data->data;
                        //maybe issuees here
                        $animals = $account->animals()->where('query_param', 'like', 'discord_%')->get();

                        $existingQueryParams = $animals->pluck('query_param')->toArray();

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
                                    'points' => ConstantValues::animal_discord_role_points,
                                    'comment' => 'Подключенные роли в дискорде '. $role,
                                    'query_param' => $role
                                ]);
                                $account->animals()->save($newAnimal);
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
            foreach ($tokenNumbers as $token){
                $process = new Process([
                    'node',
                    base_path('node/getTokenHistory.js'),
                    $token,
                    $account->wallet,
                ]);

                $process->run();
                if ($process->isSuccessful()) {

                    $animals = $account->animals()->where('query_param', 'like', 'token_owner_year_%')->get();

                    $existingQueryParams = $animals->pluck('query_param')->toArray();

                    $data = json_decode($process->getOutput());
                    if($data->state === 'success' && !is_null($data->data)){

                        $queryParam = 'token_owner_year_' . $token;
                        $this->info($data->data);
                        if (!in_array($queryParam, $existingQueryParams)) {
                            $newAnimal = new DigitalAnimal([
                                'points' => ConstantValues::animal_discord_role_points,
                                'comment' => $data->data,
                                'query_param' => $queryParam
                            ]);
                            $account->animals()->save($newAnimal);
                        }

                    }elseif($data->state === 'error' && !is_null($data->data)){
                        continue;
//                        $this->info($token);
//                        $this->info($account->wallet);
//                        $this->info($data->data);
                    }
                }
            }

        }


    }

}
