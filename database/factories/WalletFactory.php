<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Wallet::class;

    public function definition()
    {
        return [
            'provider_id' => User::factory(),
            'balance' => $this->faker->randomFloat(2, 500000, 1000000), // Random balance between 0 and 1000
        ];
    }
}
