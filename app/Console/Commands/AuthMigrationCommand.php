<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AuthMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auth-migration-command';

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
        $authorizationToken = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJlNDMwMDliNi0yYWYzLTQ4NDQtYTk5NC1lZWZiOWY4ZTgwOGUiLCJleHAiOjE3MDk3NDA3NTksIm5hbWUiOiJQYXNoYSIsImVtYWlsIjoicGF2ZWxwcmZudEBnbWFpbC5jb20iLCJ3YWxsZXRfYWRkcmVzcyI6IjB4MjliOTIxN2Q1YjA0NWYwNEY1OEY2NDFFOEVkQmQ4YkVmY2M2MTQ5YyIsImlzX2FkbWluIjp0cnVlLCJkaXNjb3JkIjp7InByb3ZpZGVyX2lkIjoiOTgzODAwNDk4NTY4MTk2MDk3IiwidXNlcl9uYW1lIjoiYnJlaW42ODA0In0sInR3aXR0ZXIiOnsicHJvdmlkZXJfaWQiOiIxNDk2ODIwMzk2NTAyNDMzNzk3IiwidXNlcl9uYW1lIjoicGhwX2FydGlzYWgiLCJuYW1lIjoicGhwIGFydGlzYW4iLCJwcm9maWxlX2ltYWdlX3VybCI6Imh0dHA6Ly9hcGkuc2FmZXNvdWwuY2x1Yi9zdG9yYWdlL3R3aXR0ZXIvYXZhdGFycy82NTY1Yzk4NmM4YjE3NzY3NjE0NjM0NmIxN2UzMDVhNy5wbmcifX0.JzKYfHS_vFw5Qakt3ULEwroHYFEGrMTXBClY7gVHwRc';

        $endpointUrl = 'https://stg-auth-app-tyqys.ondigitalocean.app/management/users/migrate';

//        $endpointUrl = 'https://stg-auth-app-tyqys.ondigitalocean.app/management/users/migrate';


        // Fetch accounts in chunks of 10
        // Counter for processed chunks
        $chunkCounter = 0;
        $accounts = Account::chunk(20, function ($chunk) use ($authorizationToken, $endpointUrl, &$chunkCounter) {
            // Prepare the request body


            $requestBody = [
                'users' => [],
            ];


            foreach ($chunk as $account) {
                $discordBlock = null;
                if ($account->discord_id !== null) {
                    $discordBlock = [
                        'id' => (string)$account->discord_id,
                        'user_name' => (string)$account->discord_name ?? null,
                    ];
                }

                $twitterBlock = null;
                if ($account->twitter_id !== null) {
                    $twitterBlock = [
                        'id' => (string)$account->twitter_id,
                        'name' => (string)$account->twitter_name ?? null,
                        'user_name' => (string)$account->twitter_username ?? null,
                        'profile_image_url' => $account->twitter_avatar ?? null,
                    ];
                }

                $requestBody['users'][] = [
                    'internal_id' => (string)$account->id,
                    'wallet_address' => (string)$account->wallet,
                    'discord' => $discordBlock,
                    'twitter' => $twitterBlock,
                ];
            }
            // var_dump($requestBody);

            // Make the API request
            $response = Http::withHeaders([
                'Authorization' => $authorizationToken,
                'Content-Type' => 'application/json',
            ])->post($endpointUrl, $requestBody);

            if ($response->successful()) {
                $responseData = $response->json();


                // Update auth_id in the database for successful responses
                foreach ($responseData['users'] as $user) {
                    Account::where('id', $user['internal_id'])->update(['auth_id' => $user['auth_id']]);
                }
                $chunkCounter++;
                $this->info($chunkCounter);
                sleep(1);
            } else {
                $responseData = $response->json();
                var_dump($responseData);


            }
        });


        return Command::SUCCESS;
    }
}
