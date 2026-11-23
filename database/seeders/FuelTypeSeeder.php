<?php

namespace Database\Seeders;

use App\Models\FuelType;
use Illuminate\Database\Seeder;

class FuelTypeSeeder extends Seeder
{
    /**
     * Seed common fuel types used in the Philippines.
     */
    public function run(): void
    {
        $fuelTypes = [
            [
                'name' => 'Diesel',
                'description' => 'Common diesel fuel for government vehicles and equipment.',
                'is_active' => true,
            ],
            [
                'name' => 'Unleaded Gasoline (91 RON)',
                'description' => 'Regular unleaded gasoline, typically 91 RON.',
                'is_active' => true,
            ],
            [
                'name' => 'Premium Gasoline (95 RON)',
                'description' => 'Premium unleaded gasoline, typically 95 RON.',
                'is_active' => true,
            ],
            [
                'name' => 'Premium Plus Gasoline (97/100 RON)',
                'description' => 'High-octane premium gasoline, typically 97 or 100 RON.',
                'is_active' => true,
            ],
            [
                'name' => 'Kerosene',
                'description' => 'Kerosene fuel for approved equipment/use.',
                'is_active' => true,
            ],
        ];

        foreach ($fuelTypes as $fuelType) {
            FuelType::query()->updateOrCreate(
                ['name' => $fuelType['name']],
                $fuelType,
            );
        }
    }
}
