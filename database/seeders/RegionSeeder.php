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
            ['region_name' => 'Latakia'],
            ['region_name' => 'Tartus'],
        ];

        // Insert regions into the database
        foreach ($regions as $region) {
            Region::create($region);
        }
    }
}
