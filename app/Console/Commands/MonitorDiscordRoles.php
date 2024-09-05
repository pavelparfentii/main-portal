<?php

namespace App\Console\Commands;

use App\Events\FarmingNFTUpdated;
use App\Models\Account;
use App\Models\FarmingDiscord;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class MonitorDiscordRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-discord-roles';

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
        $process = new Process(['node', './node/discord/discord-bot/index.js']);
        $process->setTimeout(null); // Prevent the process from timing out

        $this->startProcess($process);

        while (true) {
            if (!$process->isRunning()) {
                $this->warn("Process stopped. Restarting...");
                $this->startProcess($process);
            }

            $output = $process->getIncrementalOutput();
            if ($output) {

                $this->info("$output");
                $this->handleOutput($output);
            }

            usleep(100000); // Sleep for 100 milliseconds
        }
    }

    private function startProcess(Process $process)
    {
        try {
            $process->start();
            $this->info("Started process");
        } catch (Exception $e) {
            $this->error("Failed to start process: " . $e->getMessage());
        }
    }

    private function handleOutput($output)
    {
        $this->info('enter');
        $farming_roles = DB::table('farming_roles')
            ->whereNotNull('role_id')
            ->pluck('role_id')
            ->toArray();
        $lines = explode(PHP_EOL, $output);

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $data = json_decode($line, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Check the role action
                if (isset($data['role']) && isset($data['discord_id']) && isset($data['role_id'])) {

                    $role = $data['role'];
                    $discordId = $data['discord_id'];
                    $roleId = $data['role_id'];

                    // Process the role action
                    if ($role === 'addRole' && in_array($roleId, $farming_roles)) {
                        $account = Account::where('discord_id', $discordId)->first();
                        if($account){
                            $farmingDiscord = FarmingDiscord::where('role_id', $roleId)->where('discord_id', $account->discord_id)->first();
                            if(!$farmingDiscord){
                                DB::table('farming_discords')->insert([
                                    'discord_id' => $discordId,
                                    'role_id' => $roleId,
                                    'created_at'=>now(),
                                    'item_points_daily' => 0.143,

                                ]);

                                FarmingNFTUpdated::dispatch(null, $account->id, $role);
                            }
                        }

                    } elseif ($role === 'removeRole') {
                        $account = Account::where('discord_id', $discordId)->first();
                        if($account){
                            $farmingDiscord = FarmingDiscord::where('role_id', $roleId)->where('discord_id', $account->discord_id)->first();
                            if($farmingDiscord){
                                $farmingDiscord->delete();

                                FarmingNFTUpdated::dispatch(null, $account->id, $role);
                            }

                        }
                    }
                }
            }
        }
    }
}
