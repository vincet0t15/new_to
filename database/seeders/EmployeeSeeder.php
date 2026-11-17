<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

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
