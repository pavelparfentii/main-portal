<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ParseDiscordRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:parse-discord-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accounts = Account::whereNotNull('discord_id')->cursor();

        foreach ($accounts as $account){
            $scriptPath = base_path('./node/discord/role/parseRoleId.js');
            $discordId = $account->discord_id;
//        dd($discordId);
            $process = new Process(['node', $scriptPath, $discordId]);
            $process->run();

// Check if the process was successful
            if ($process->isSuccessful()) {

                // Get the output of the script
                sleep(1);
                $output = $process->getOutput();


                $result = json_decode($output, true);
                var_dump($result);
                if ($result['state'] === 'success') {

                    $data = $result['data'];
                    var_dump($data);

                    if(is_array($data)){

                       $farming_roles = DB::table('farming_roles')
                           ->whereNotNull('role_id')
                           ->pluck('role_id')
                           ->toArray();

                        foreach ($data as $roleId){
                            if(in_array($roleId, $farming_roles)){
                                var_dump('here');
                                DB::table('farming_discords')->insert([
                                   'discord_id'=> $discordId,
                                    'role_id'=> $roleId,
                                    'item_points_daily'=>0.143
                                ]);
                            }
                        }
                    }


                }
            } else {

                Log::info($process->getErrorOutput());
//                return null;
            }
        }




    }
}
