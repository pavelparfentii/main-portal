<?php

namespace App\Console\Commands;

use App\Models\DiscordRole;
use Illuminate\Console\Command;

class UpdateDiscordRolesTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-discord-roles-table-command';

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

        $discordRoles = [
            [
                "role" => "Artist",
                "id" => "913784130028851200",
                "color" => "c2e900",
                "position" => 1
            ],
            [
                "role" => "team",
                "id" => "912651088342183996",
                "color" => "c2e900",
                "position" => 2
            ],
            [
                "role" => "SafeSoul team",
                "id" => "1067501151194460240",
                "color" => "c2e900",
                "position" => 3
            ],
            [
                "role" => "Soul",
                "id" => "1127990767223320757",
                "color" => "D65745",
                "position" => 4
            ],
            [
                "role" => "admin",
                "id" => "890566611919052881",
                "color" => "D8833B",
                "position" => 5
            ],
            [
                "role" => "Souldiers",
                "id" => "915704909167480893",
                "color" => "5296D5",
                "position" => 6
            ],
            [
                "role" => "Lords / Ladies",
                "id" => "921807378985390131",
                "color" => "EAC645",
                "position" => 7
            ],
            [
                "role" => "Patrol",
                "id" => "1138407266102554624",
                "color" => "65C97A",
                "position" => 8
            ],
            [
                "role" => "OG Patrol",
                "id" => "1138407911492702268",
                "color" => "c2e900",
                "position" => 9
            ],
            [
                "role" => "digital animals",
                "id" => "921351777532657695",
                "color" => "6811F5",
                "position" => 10
            ],
            [
                "role" => "Soul Reaper",
                "id" => "977443709316648991",
                "color" => "448952",
                "position" => 11
            ],
            [
                "role" => "Soulborn",
                "id" => "1029647216106815579",
                "color" => "925CB1",
                "position" => 12
            ],
            [
                "role" => "demo-testing",
                "id" => "1044888119389978654",
                "color" => "B9B9B9",
                "position" => 13
            ],
            [
                "role" => "888",
                "id" => "918040386440659024",
                "color" => "EAD9FC",
                "position" => 14
            ],
            [
                "role" => "Server Booster",
                "id" => "856828969180856320",
                "color" => "E585F8",
                "position" => 15
            ],
            [
                "role" => "DA_NFT",
                "id" => "1142856314389798963",
                "color" => "B8CE97",
                "position" => 16
            ],
            [
                "role" => "DigitalSouls",
                "id" => "1143539187337920662",
                "color" => "B8CE97",
                "position" => 17
            ],
            [
                "role" => "SafeSoul",
                "id" => "1128990103117774848",
                "color" => "F6FFC0",
                "position" => 18
            ],
            [
                "role" => "SoulStore",
                "id" => "1143539055087337492",
                "color" => "C5D0FB",
                "position" => 19
            ],
            [
                "role" => "DA Game",
                "id" => "1143539291646070834",
                "color" => "F5BFE0",
                "position" => 20
            ],
            [
                "role" => "Italian 🇮🇹",
                "id" => "938442407903649812",
                "color" => "9CA9B4",
                "position" => 21
            ],
            [
                "role" => "French 🇫🇷",
                "id" => "938442610186534972",
                "color" => "9CA9B4",
                "position" => 22
            ],
            [
                "role" => "Arabic 🇦🇪",
                "id" => "938442624149372998",
                "color" => "9CA9B4",
                "position" => 23
            ],
            [
                "role" => "Chinese 🇨🇳",
                "id" => "938442631099342871",
                "color" => "9CA9B4",
                "position" => 24
            ],
            [
                "role" => "Philippine 🇵🇭",
                "id" => "938442632873541702",
                "color" => "9CA9B4",
                "position" => 25
            ],
            [
                "role" => "Norwegian 🇳🇴",
                "id" => "938442635583041556",
                "color" => "9CA9B4",
                "position" => 26
            ],
            [
                "role" => "Polish 🇵🇱",
                "id" => "938443135997075497",
                "color" => "9CA9B4",
                "position" => 27
            ],
            [
                "role" => "Turkish 🇹🇷",
                "id" => "938442630239502338",
                "color" => "9CA9B4",
                "position" => 28
            ],
            [
                "role" => "Vietnamese 🇻🇳",
                "id" => "938443136533930025",
                "color" => "9CA9B4",
                "position" => 29
            ],
            [
                "role" => "Malaysian 🇲🇾",
                "id" => "938443137343438930",
                "color" => "9CA9B4",
                "position" => 30
            ],
            [
                "role" => "Dutch 🇳🇱",
                "id" => "938442635075526697",
                "color" => "9CA9B4",
                "position" => 31
            ],
            [
                "role" => "German 🇩🇪",
                "id" => "938442633527820358",
                "color" => "9CA9B4",
                "position" => 32
            ],
            [
                "role" => "Hebrew 🇮🇱",
                "id" => "938443138291355679",
                "color" => "9CA9B4",
                "position" => 33
            ],
            [
                "role" => "Indonesian 🇮🇩",
                "id" => "938443137607688292",
                "color" => "9CA9B4",
                "position" => 34
            ],
            [
                "role" => "Russian 🇷🇺",
                "id" => "938442628234616913",
                "color" => "9CA9B4",
                "position" => 35
            ],
            [
                "role" => "Japanese 🇯🇵",
                "id" => "938442626280095745",
                "color" => "9CA9B4",
                "position" => 36
            ],
            [
                "role" => "Korean 🇰🇷",
                "id" => "938442131062804501",
                "color" => "9CA9B4",
                "position" => 37
            ],
            [
                "role" => "Spanish🇪🇸",
                "id" => "938441096638382100",
                "color" => "9CA9B4",
                "position" => 38
            ],
            [
                "role" => "portuguese 🇵🇹",
                "id" => "938731635359244318",
                "color" => "9CA9B4",
                "position" => 39
            ],
            [
                "role" => "alpha",
                "id" => "945023447044595742",
                "color" => "C1FBFE",
                "position" => 40
            ],
            [
                "role" => "GG",
                "id" => "968568827103305738",
                "color" => "FAE0AA",
                "position" => 41
            ],
            [
                "role" => "verified",
                "id" => "905024124508852235",
                "color" => "010101",
                "position" => 42
            ],
            [
                "role" => "Security-class",
                "id" => "1163859503410860136",
                "color" => "9CA9B4",
                "position" => 43
            ],
            [
                "role" => "Scout",
                "id" => "1164531108172542004",
                "color" => "9CA9B4",
                "position" => 44
            ],
            [
                "role" => "Observer",
                "id" => "1164531239282290698",
                "color" => "9CA9B4",
                "position" => 45
            ]
        ];

        foreach ($discordRoles as $role)
            DiscordRole::updateOrCreate(
                ['role_id' => $role['id']],
                [
                    'name' => $role['role'],
                    'color' => $role['color'],
                    'position' => $role['position']
                ]
        );

        $this->info('success');
    }
}
