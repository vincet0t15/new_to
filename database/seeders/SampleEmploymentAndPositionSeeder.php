<?php

namespace Database\Seeders;

use App\Models\EmploymentType;
use App\Models\Position;
use Illuminate\Database\Seeder;

class SampleEmploymentAndPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employmentTypes = [
            [
                'name' => 'Regular',
                'description' => 'Permanent full-time employee.',
            ],
            [
                'name' => 'Contractual',
                'description' => 'Fixed-term employee under contract.',
            ],
            [
                'name' => 'Casual',
                'description' => 'Part-time or project-based employee.',
            ],
            [
                'name' => 'Job Order',
                'description' => 'Short-term assignment through job order.',
            ],
        ];

        $typeMap = [];

        foreach ($employmentTypes as $employmentType) {
            $record = EmploymentType::firstOrCreate(
                ['name' => $employmentType['name']],
                ['description' => $employmentType['description']],
            );

            $typeMap[$employmentType['name']] = $record->id;
        }

        $positions = [
            ['name' => 'Administrative Officer', 'employment_type' => 'Regular'],
            ['name' => 'Senior Administrative Assistant', 'employment_type' => 'Regular'],
            ['name' => 'Human Resources Officer', 'employment_type' => 'Regular'],
            ['name' => 'Finance Analyst', 'employment_type' => 'Regular'],
            ['name' => 'Project Coordinator', 'employment_type' => 'Contractual'],
            ['name' => 'IT Support Specialist', 'employment_type' => 'Contractual'],
            ['name' => 'Operations Assistant', 'employment_type' => 'Casual'],
            ['name' => 'Field Enumerator', 'employment_type' => 'Job Order'],
            ['name' => 'Driver', 'employment_type' => 'Job Order'],
            ['name' => 'Office Clerk', 'employment_type' => 'Casual'],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(
                ['name' => $position['name']],
                [
                    'description' => 'Sample position for '.$position['name'].'.',
                    'employment_type_id' => $typeMap[$position['employment_type']] ?? null,
                ],
            );
        }
    }
}
