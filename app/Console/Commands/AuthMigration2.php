<?php

namespace App\Console\Commands;

use App\Models\Account;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthMigration2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auth-migration2';

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

        $endpointUrl = 'https://auth.digitalsouls.club/management/users';

        $accounts = Account::all();

        foreach ($accounts as $account) {
            // Ensure the account has an auth_id before making the request
            if (!empty($account->auth_id)) {
                $response = Http::withHeaders([
                    'Authorization' => $authorizationToken,
                    'Content-Type' => 'application/json',
                ])->get($endpointUrl, ['user_uuid' => $account->auth_id]);


                if ($response->successful()) {
                    $userData = $response->json();
                    if(!empty($userData['discord'])){
                        $account->update([
                            'discord_id'=>$userData['discord']['id'],
                        ]);
                    }elseif (!empty($userData['twitter'])){

                        $avatarUrl = $this->downloadTwitterAvatar($userData['twitter']['profile_image_url']);
                        $account->update([
                            'twitter_id'=>$userData['twitter']['id'],
                            'twitter_name'=>$userData['twitter']['name'],
                            'twitter_username'=>$userData['twitter']['user_name'],
                            'twitter_avatar'=>$avatarUrl
                        ]);
                    }

                } else {
                    // Handle error (log it, throw an exception, etc.)
                    // For example:
                    Log::error('Failed to fetch user data for auth_id: ' . $account->auth_id);
                }
            }
        }

        return '$usersData';
    }

    public function downloadTwitterAvatar($result): ?string
    {
        $TWITTER_AVATAR_PATH = 'twitter/avatars';

        try {

            if (isset($result)) {

                $url = $result;

                $contents = file_get_contents($url);

                $filename = $TWITTER_AVATAR_PATH. '/'. md5($url) . '.' . pathinfo($url, PATHINFO_EXTENSION);

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
