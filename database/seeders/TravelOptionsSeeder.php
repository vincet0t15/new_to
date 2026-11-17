<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use App\Models\FundSource;
use App\Models\TransportationMode;
use App\Models\TravelClassification;
use Illuminate\Database\Seeder;

/**
 * The reference values a travel order header needs before it can be filed.
 *
 * Locations are deliberately not seeded: they describe the municipality's own
 * routes and are configured by an administrator in Travel Setup.
 */
class TravelOptionsSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const TRAVEL_CLASSIFICATIONS = [
        'Official Business',
        'Official Time',
    ];

    /**
     * @var array<int, string>
     */
    private const FUND_SOURCES = [
        'General Fund',
        'Special Education Fund',
    ];

    /**
     * @var array<int, string>
     */
    private const TRANSPORTATION_MODES = [
        'Van',
        'Taxi',
        'Airplane',
        'Ferry',
        'Private Vehicle',
        'Public Utility Vehicle',
        'Government Vehicle',
    ];

    /**
     * @var array<string, array<string, bool>>
     */
    private const EXPENSE_TYPES = [
        'Traveling Expenses' => ['is_transportation' => true],
        'Hotel Accommodation' => [],
        'Official Receipt' => [],
        'Registration' => [],
        'Per Diems' => [],
        'Others' => [],
    ];

    public function run(): void
    {
        foreach (self::TRAVEL_CLASSIFICATIONS as $name) {
            TravelClassification::firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }

        foreach (self::FUND_SOURCES as $name) {
            FundSource::firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }

        foreach (self::TRANSPORTATION_MODES as $name) {
            $mode = TransportationMode::firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );

            if ($name === 'Government Vehicle') {
                $mode->update(['is_government' => true, 'fix_cost' => 0]);
            }
        }

        foreach (self::EXPENSE_TYPES as $name => $flags) {
            ExpenseType::firstOrCreate(
                ['name' => $name],
                [
                    ...$flags,
                    'is_default' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
