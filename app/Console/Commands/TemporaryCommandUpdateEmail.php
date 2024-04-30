<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TemporaryCommandUpdateEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:temporary-command-update-email';

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
        $authorizationToken = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJlNDMwMDliNi0yYWYzLTQ4NDQtYTk5NC1lZWZiOWY4ZTgwOGUiLCJleHAiOjE3MTMzNTU3ODQsIm5hbWUiOiJQYXNoYSIsImVtYWlsIjoicGF2ZWxwcmZudEBnbWFpbC5jb20iLCJ3YWxsZXRfYWRkcmVzcyI6IjB4MjliOTIxN2Q1YjA0NWYwNEY1OEY2NDFFOEVkQmQ4YkVmY2M2MTQ5YyIsImlzX2FkbWluIjp0cnVlLCJkaXNjb3JkIjp7InByb3ZpZGVyX2lkIjoiOTgzODAwNDk4NTY4MTk2MDk3IiwidXNlcl9uYW1lIjoiYnJlaW42ODA0In0sInR3aXR0ZXIiOnsicHJvdmlkZXJfaWQiOiIxNDk2ODIwMzk2NTAyNDMzNzk3IiwidXNlcl9uYW1lIjoicGhwX2FydGlzYWgiLCJuYW1lIjoicGhwIGFydGlzYW4iLCJwcm9maWxlX2ltYWdlX3VybCI6Imh0dHBzOi8vcGJzLnR3aW1nLmNvbS9wcm9maWxlX2ltYWdlcy8xNDk2ODIwNDUzMTc1OTY3NzQ5L2lQZ2tmazV6LnBuZyJ9fQ.5H8uMEBQ0lhj55oqTIEOZ0BhkH0yHxtn4bJsUaxTrpk';

        $endpointUrl = 'https://auth.digitalsouls.club/management/users/';

        $array = Account::whereNotNull('auth_id')->pluck('auth_id')->toArray();


        $response = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $authorizationToken
        ])->withBody(json_encode($array), 'application/json')->get($endpointUrl);

        if ($response->successful()) {
            $usersData = $response->json();
            $bar = $this->output->createProgressBar(count($usersData['users']));
            $bar->start();
//            dd($usersData);
            foreach ($usersData['users'] as $datum){

                $bar->advance();

                $account = Account::where('auth_id', $datum['uuid'])->first();
                if($account){
                    $userWallet = !is_null($datum['wallet_address']) ? strtolower($datum['wallet_address']) : null;
                    $TwitterId = !is_null($datum['twitter']) ? $datum['twitter']['id'] : null;
                    $TwitterName = !is_null($datum['twitter']) ? $datum['twitter']['name'] : null;
                    $TwitterUsername =!is_null($datum['twitter']) ? strtolower($datum['twitter']['user_name']) : null;
                    $TwitterAvatar = !is_null($datum['twitter']) ? $datum['twitter']['profile_image_url'] : null;
                    $discordUserName = !is_null($datum['discord']) ? $datum['discord']['user_name'] : null;
                    $discordId = !is_null($datum['discord']) ? $datum['discord']['id'] : null;
                    $email = !is_null($datum['email']) ? strtolower($datum['email']) : null;
                    $account->update(['email'=>$email]);

                }
                $bar->finish();
            }

        }
    }
}
