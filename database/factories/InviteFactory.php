<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invite>
 */
class InviteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invited_by' => null,
            'whom_invited' => null,
            'inviter_wallet'=>'empty',
            'invitee_wallet'=>'empty',
            'code_id' => 2,
            'used_code' => "ABC",
        ];
    }
}
