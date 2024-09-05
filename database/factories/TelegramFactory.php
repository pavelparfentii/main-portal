<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Telegram>
 */
class TelegramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => null, // це поле буде встановлено пізніше
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'telegram_username'=>$this->faker->lastName,
        ];
    }
}
