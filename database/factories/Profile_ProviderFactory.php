<?php

namespace Database\Factories;

use App\Models\Image;
use App\Models\Profile_Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile_Provider>
 */
class Profile_ProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Profile_Provider::class;

    public function definition()
    {
        return [
            'provider_id' => User::factory(), // Creates a user if not provided
            'service_id' => Service::inRandomOrder()->first()->id,
            'image_id' => Image::inRandomOrder()->first()->id,
            'years_experience' => $this->faker->numberBetween(1, 10),
            'phone' => 96539624,
            'description' => $this->faker->sentence,
        ];
    }
}
