<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * The first two locations an install needs before a travel order can be filed.
 *
 * They mirror the reference application's sample trip: the municipal hall is
 * the usual origin and official station, and the provincial capitol is the
 * usual destination. An administrator adds the rest through Travel Setup.
 */
class LocationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @var array<int, string>
     */
    private const LOCATIONS = [
        'Municipal Hall',
        'Provincial Capitol',
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
