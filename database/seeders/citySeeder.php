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
            'Hama'         => ['Hama City', 'Salamiya', 'Masyaf', 'Soran'],
            'Latakia'      => ['Latakia City', 'Jableh', 'Qardaha', 'Kasab'],
            'Idlib'        => ['Idlib City', 'Maarrat al-Numan', 'Ariha', 'Saraqib'],
            'Deir ez-Zor'  => ['Deir ez-Zor City', 'Al-Mayadin', 'Abu Kamal', 'Al-Asharah'],
            'Al-Hasakah'   => ['Hasakah City', 'Qamishli', 'Ras al-Ayn', 'Amuda'],
            'Raqqa'        => ['Raqqa City', 'Tal Abyad', 'Al-Thawrah', 'Al-Safira'],
            'Daraa'        => ['Daraa City', 'Nawa', 'Jasim', 'Inkhil'],
            'Tartus'       => ['Tartus City', 'Baniyas', 'Safita', 'Al-Shaykh Badr'],
            'Quneitra'     => ['Quneitra City', 'Khan Arnabah', 'Al-Harra', 'Al-Hamidiyah'],
            'As-Suwayda'   => ['As-Suwayda City', 'Shahba', 'Salkhad', 'Qanawat'],
            'Rif Dimashq'  => ['Douma', 'Harasta', 'Duma', 'Jaramana'],
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
