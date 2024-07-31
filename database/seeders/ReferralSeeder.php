<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Invite;
use App\Models\Telegram;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use function Symfony\Component\HttpKernel\Log\format;

class ReferralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        $specificAccount = Account::factory()->create();


        // Створюємо 100 рефералів для конкретного акаунта
        $referrals = Account::factory()
            ->count(100)
            ->create();

        foreach ($referrals as $referral){
            $specificAccountId = 9;
            Invite::factory()
                ->count(1)
                ->create([
                    'invited_by' => $specificAccountId,
                    'whom_invited' => $referral->id,
                ]);

            Telegram::factory()->create([
                'account_id' => $referral->id,
            ]);
        }

        $referrals->each(function ($referral) {
            $subReferrals = Account::factory()
                ->count(2)
                ->create();

            foreach ($subReferrals as $subReferral) {
                Invite::factory()
                    ->count(1)
                    ->create([
                        'invited_by' => $referral->id,
                        'whom_invited' => $subReferral->id,
                    ]);
                Telegram::factory()->create([
                    'account_id' => $subReferral->id,
                ]);
            }
        });
    }
}
