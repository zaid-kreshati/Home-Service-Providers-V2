<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            ['region_name' => 'Damascus'],
            ['region_name' => 'Aleppo'],
            ['region_name' => 'Homs'],
            ['region_name' => 'Hama'],
            ['region_name' => 'Latakia'],
            ['region_name' => 'Idlib'],
            ['region_name' => 'Deir ez-Zor'],
            ['region_name' => 'Al-Hasakah'],
            ['region_name' => 'Raqqa'],
            ['region_name' => 'Daraa'],
            ['region_name' => 'Tartus'],
            ['region_name' => 'Quneitra'],
            ['region_name' => 'As-Suwayda'],
            ['region_name' => 'Rif Dimashq'],
        ];

        // Insert regions into the database
        foreach ($regions as $region) {
            Region::create($region);
        }
    }
}
