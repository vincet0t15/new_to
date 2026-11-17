<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use App\Models\FundSource;
use App\Models\MealType;
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

    /**
     * The meal breakdowns a per diem configuration may use.
     *
     * Breakfast, lunch and dinner carry a cutoff time used by the claim
     * computation; incidental and other meals are paid in full.
     *
     * @var array<int, array{code: string, name: string, has_cutoff: bool, sort_order: int}>
     */
    private const MEAL_TYPES = [
        ['code' => 'breakfast', 'name' => 'Breakfast', 'has_cutoff' => true, 'sort_order' => 1],
        ['code' => 'lunch', 'name' => 'Lunch', 'has_cutoff' => true, 'sort_order' => 2],
        ['code' => 'dinner', 'name' => 'Dinner', 'has_cutoff' => true, 'sort_order' => 3],
        ['code' => 'incidental', 'name' => 'Incidental', 'has_cutoff' => false, 'sort_order' => 4],
        ['code' => 'other', 'name' => 'Other', 'has_cutoff' => false, 'sort_order' => 5],
    ];

    public function run(): void
    {
        foreach (self::MEAL_TYPES as $mealType) {
            MealType::firstOrCreate(
                ['code' => $mealType['code']],
                [
                    'name' => $mealType['name'],
                    'has_cutoff' => $mealType['has_cutoff'],
                    'is_active' => true,
                    'sort_order' => $mealType['sort_order'],
                ],
            );
        }

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
