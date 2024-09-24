<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Image;
use App\Models\Profile_Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all cities
        $cities = City::all();

        // Get all services
        $services = Service::all();

        // Create provider role if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'provider']);

        // Create 25 providers
        for ($i = 0; $i < 25; $i++) {
            $city = $cities->random();
            $service = $services->random();

            // Create a user with role provider using the factory
            $user = User::factory()->create([
                'city_id' => $city->id,
            ]);

            // Assign provider role to the user
            $user->assignRole($role);

            // Create a profile for the provider using the factory
            Profile_Provider::factory()->create([
                'provider_id' => $user->id,
                'service_id' => $service->id,
                'description' => 'Experienced ' . $service->name . ' provider in ' . $city->city_name,
            ]);

            // Create a wallet for the provider using the factory
            Wallet::factory()->create([
                'provider_id' => $user->id,
            ]);
        }
    }
}
