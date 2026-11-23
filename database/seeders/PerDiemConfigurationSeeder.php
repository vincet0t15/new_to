<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\PerDiemConfiguration;
use App\Models\PerDiemMealRule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerDiemConfigurationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @var array<int, array{meal_type:string,name:string,amount:string,cutoff_time:?string,sort_order:int}>
     */
    private const MEALS = [
        ['meal_type' => 'breakfast', 'name' => 'Breakfast', 'amount' => '100.00', 'cutoff_time' => '06:00', 'sort_order' => 1],
        ['meal_type' => 'lunch', 'name' => 'Lunch', 'amount' => '150.00', 'cutoff_time' => '12:00', 'sort_order' => 2],
        ['meal_type' => 'dinner', 'name' => 'Dinner', 'amount' => '150.00', 'cutoff_time' => '18:00', 'sort_order' => 3],
    ];

    public function run(): void
    {
        Location::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->each(function (Location $location): void {
                $configuration = PerDiemConfiguration::query()->firstOrCreate(
                    ['name' => "Per Diem - {$location->name}"],
                    [
                        'effective_from' => now()->startOfYear()->toDateString(),
                        'effective_until' => null,
                        'daily_rate' => null,
                        'is_active' => true,
                    ],
                );

                $configuration->locations()->syncWithoutDetaching([$location->id]);

                foreach (self::MEALS as $meal) {
                    PerDiemMealRule::query()->firstOrCreate(
                        [
                            'per_diem_configuration_id' => $configuration->id,
                            'meal_type' => $meal['meal_type'],
                        ],
                        [
                            'name' => $meal['name'],
                            'amount' => $meal['amount'],
                            'cutoff_time' => $meal['cutoff_time'],
                            'sort_order' => $meal['sort_order'],
                        ],
                    );
                }
            });
    }
}
