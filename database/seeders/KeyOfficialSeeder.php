<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowStepType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Seed key officials with positions and login accounts.
 *
 * This is operational/demo data, not called by DatabaseSeeder. Run explicitly:
 *
 *   php artisan db:seed --class=KeyOfficialSeeder
 *
 * Accounts are created with password "password" only on first creation.
 */
class KeyOfficialSeeder extends Seeder
{
    use WithoutModelEvents;

    private const PASSWORD = 'password';

    /**
     * @var array<int, array{
     *     username: string,
     *     email: string,
     *     first_name: string,
     *     middle_name: string|null,
     *     last_name: string,
     *     suffix: string|null,
     *     office_code: string,
     *     position: string,
     *     roles: array<int, SystemRole>
     * }>
     */
    private const PEOPLE = [
        [
            'username' => 'ramir',
            'email' => 'ramir.pablico@example.com',
            'first_name' => 'Ramir',
            'middle_name' => 'R.',
            'last_name' => 'Pablico',
            'suffix' => null,
            'office_code' => 'MO',
            'position' => 'Municipal Mayor',
            'roles' => [SystemRole::Employee, SystemRole::Mayor],
        ],
        [
            'username' => 'xherluck',
            'email' => 'xherluck.loriega@example.com',
            'first_name' => 'Xherluck',
            'middle_name' => null,
            'last_name' => 'Loriega',
            'suffix' => null,
            'office_code' => 'MBO',
            'position' => 'Municipal Budget Officer',
            'roles' => [SystemRole::Employee, SystemRole::BudgetOfficer],
        ],
        [
            'username' => 'marible',
            'email' => 'maribel.ubadillo@example.com',
            'first_name' => 'Maribel',
            'middle_name' => 'P.',
            'last_name' => 'Ubadillo',
            'suffix' => null,
            'office_code' => 'HRMO',
            'position' => 'HRMO',
            'roles' => [SystemRole::Employee, SystemRole::Hrmo],
        ],
        [
            'username' => 'zyrus',
            'email' => 'zyrus.famini@example.com',
            'first_name' => 'Zyrus Vince',
            'middle_name' => 'B.',
            'last_name' => 'Famini',
            'suffix' => null,
            'office_code' => 'MO',
            'position' => 'Computer Programmer',
            'roles' => [SystemRole::Employee],
        ],
        [
            'username' => 'vicemayor.mechie',
            'email' => 'mechie.silagan@example.com',
            'first_name' => 'Mechie',
            'middle_name' => 'L.',
            'last_name' => 'Silagan',
            'suffix' => null,
            'office_code' => 'SBO',
            'position' => 'Municipal Vice Mayor',
            'roles' => [SystemRole::Employee, SystemRole::ViceMayor],
        ],
    ];

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            OfficeSeeder::class,
            SampleEmploymentAndPositionSeeder::class,
            WorkflowSeeder::class,
        ]);

        $employmentType = EmploymentType::query()->firstOrCreate(
            ['name' => 'Regular'],
            ['description' => 'Permanent full-time employee.'],
        );

        $employees = [];

        foreach (self::PEOPLE as $person) {
            $office = Office::query()->where('office_code', $person['office_code'])->first();

            if (! $office instanceof Office) {
                throw new RuntimeException(
                    'Office code '.$person['office_code'].' is missing. Run OfficeSeeder first.'
                );
            }

            $position = Position::query()->firstOrCreate(
                ['name' => $person['position']],
                [
                    'description' => $person['position'].' position.',
                    'employment_type_id' => $employmentType->id,
                ],
            );

            $user = User::query()->firstOrNew(['username' => $person['username']]);

            $employee = $user->employee instanceof Employee
                ? $user->employee
                : Employee::query()->firstOrNew([
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'office_id' => $office->id,
                ]);

            $employee->fill([
                'first_name' => $person['first_name'],
                'middle_name' => $person['middle_name'],
                'last_name' => $person['last_name'],
                'suffix' => $person['suffix'],
                'position_id' => $position->id,
                'office_id' => $office->id,
                'employment_type_id' => $employmentType->id,
                'is_active' => true,
            ])->save();

            $user->fill([
                'name' => $this->displayName($person),
                'email' => $person['email'],
                'employee_id' => $employee->id,
            ]);

            if (! $user->exists) {
                $user->password = Hash::make(self::PASSWORD);
                $user->email_verified_at = Carbon::now();
            }

            $user->save();

            $roles = Role::query()
                ->whereIn('slug', array_map(
                    fn (SystemRole $role): string => $role->value,
                    $person['roles'],
                ))
                ->get();

            $user->syncRoles($roles->all());
            $employees[$person['username']] = $employee;
        }

        $this->seedDesignations($employees);
    }

    /**
     * Seed routing designations for key officials.
     *
     * Department Head designations are office-specific and are intentionally
     * not guessed here. Administrators can assign them per office as needed.
     *
     * @param  array<string, Employee>  $employees
     */
    private function seedDesignations(array $employees): void
    {
        $designations = [
            ['office' => 'MBO', 'step' => 'Budget Review', 'username' => 'xherluck'],
            ['office' => 'MO', 'step' => 'Mayor Approval', 'username' => 'ramir'],
            ['office' => 'HRMO', 'step' => 'HRMO Review', 'username' => 'marible'],
            ['office' => 'VMAYOR', 'step' => 'Vice Mayor Approval', 'username' => 'vicemayor.mechie'],
        ];

        $stepTypes = WorkflowStepType::query()
            ->whereIn('name', array_column($designations, 'step'))
            ->get()
            ->keyBy('name');

        foreach ($designations as $definition) {
            $office = Office::query()->where('office_code', $definition['office'])->first();
            $stepType = $stepTypes->get($definition['step']);
            $employee = $employees[$definition['username']] ?? null;

            if (! $office instanceof Office || ! $stepType instanceof WorkflowStepType || ! $employee instanceof Employee) {
                throw new RuntimeException('Unable to seed designation for '.$definition['username'].'.');
            }

            OfficeDesignation::query()->updateOrCreate(
                [
                    'office_id' => $office->id,
                    'workflow_step_type_id' => $stepType->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'is_deputy' => false,
                    'is_active' => true,
                    'starts_at' => null,
                    'ends_at' => null,
                ],
            );
        }
    }

    /**
     * @param  array{first_name: string, middle_name: string|null, last_name: string, suffix: string|null}  $person
     */
    private function displayName(array $person): string
    {
        return collect([
            $person['first_name'],
            $person['middle_name'],
            $person['last_name'],
            $person['suffix'],
        ])->filter()->implode(' ');
    }
}
