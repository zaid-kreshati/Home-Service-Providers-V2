<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class citySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all regions
        $regions = Region::all();

        // Define four cities for each region
        $cities = [
            'Damascus'     => ['Damascus City', 'Mazzeh'],
            'Aleppo'       => ['Aleppo City'],
            'Homs'         => ['Homs City', 'Qusayr'],
            'Latakia'      => ['Latakia City', 'Jableh', 'Qardaha', 'Kasab'],
            'Tartus'       => ['Tartus City', 'Baniyas', 'Safita', 'Al-Shaykh Badr'],
        ];

        // Insert cities into the database
        foreach ($regions as $region) {
            if (isset($cities[$region->region_name])) {
                foreach ($cities[$region->region_name] as $cityName) {
                    City::create([
                        'city_name' => $cityName,
                        'region_id' => $region->id,
                    ]);
                }
            }
        }
    }
}
