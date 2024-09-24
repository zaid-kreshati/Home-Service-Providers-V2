<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic' ,
                'price'=> 80000,
                'duration'=> 'Month'
            ],
            [
                'name' => 'Pro' ,
                'price'=> 900000,
                'duration'=> 'Year'
            ]
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }

    }
}
