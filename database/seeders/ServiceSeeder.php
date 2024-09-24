<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $images = Image::all();
        $services = [
            [
                'name' => 'Cleaning',
                'description' => 'Professional cleaning services for homes and offices.',
                'image_id' => $images[0]->id  // Corrected index
            ],
            [
                'name' => 'Plumber',
                'description' => 'Expert plumbing services including repairs and installations.',
                'image_id' => $images[1]->id  // Corrected index
            ],
            [
                'name' => 'Electricity',
                'description' => 'Reliable electrical services for residential and commercial needs.',
                'image_id' => $images[2]->id  // Corrected index
            ],
            [
                'name' => 'Painting',
                'description' => 'Quality painting services for interior and exterior surfaces.',
                'image_id' => $images[3]->id  // Corrected index
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
