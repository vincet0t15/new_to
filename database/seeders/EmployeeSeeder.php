<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Ten Faker employees, each in a Faker office, for local demos.
 *
 * This is deliberately not part of DatabaseSeeder: a real install starts with
 * the official office directory and the Administrator's own employees. Run it
 * explicitly when a local demo needs populated pickers.
 */
class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $missingEmployees = max(0, 10 - Employee::query()->count());

        if ($missingEmployees > 0) {
            Employee::factory()->count($missingEmployees)->create();
        }
    }
}
