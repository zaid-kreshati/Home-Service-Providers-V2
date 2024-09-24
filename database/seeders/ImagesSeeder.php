<?php

namespace Database\Seeders;

use App\Models\Image;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define some sample image paths
        $images = [
            ['path' =>'cleaning_service.jpg'],
            ['path' =>'plumber_service.jpg'],
            ['path' =>'electricity_service.jpg'],
            ['path' =>'painting_service.jpg'],
            ['path' => 'images/sample1.jpg'],
            ['path' => 'images/sample2.jpg'],
            ['path' => 'images/sample3.jpg'],

        ];

        // Insert images into the database
        foreach ($images as $image) {
            Image::create($image);
        }
    }
}
