<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Default locations seeded for fresh installs/local setup.
 *
 * Keeps baseline municipal locations and adds common LGU travel destinations.
 */
class LocationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @var array<int, string>
     */
    private const LOCATIONS = [
        'San Vicente, Palawan',
        'Puerto Princesa City, Palawan',
    ];

    public function run(): void
    {
        foreach (self::LOCATIONS as $name) {
            Location::firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
