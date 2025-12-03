<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobPackage;

class JobPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price' => 100000,
                'duration_days' => 30,
                'features' => json_encode(['listing_duration' => 30, 'featured' => false]),
                'active' => true
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'price' => 200000,
                'duration_days' => 60,
                'features' => json_encode(['listing_duration' => 60, 'priority' => 'medium']),
                'active' => true
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 300000,
                'duration_days' => 90,
                'features' => json_encode(['listing_duration' => 90, 'priority' => 'high']),
                'active' => true
            ],
        ];

        foreach ($packages as $p) {
            JobPackage::updateOrCreate(['slug' => $p['slug']], $p);
        }
    }
}

