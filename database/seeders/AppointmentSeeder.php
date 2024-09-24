<?php

namespace Database\Seeders;

use App\Models\Appointment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 random appointments
        for ($i = 0; $i < 15; $i++) {
            Appointment::create([
                'client_id' => 3, // Random client ID
                'provider_id' => 2, // Random provider ID
                'date' => now()->addDays(rand(1, 30))->format('Y-m-d'), // Random future date
                'hours' => rand(1, 24), // Random hour between 1 and 24
                'status' => Arr::random(['pending', 'approved', 'canceled', 'finished']), // Random status
                'description' => Str::random(20), // Random description
            ]);
        }
    }
}
