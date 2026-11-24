<?php

namespace Database\Seeders;

use App\Concerns\ResolvesSeedCredentials;
use App\Enums\SystemRole;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Location;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\PerDiemConfiguration;
use App\Models\PerDiemMealRule;
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
 * A small, fully connected organization for exercising the approval chain.
 *
 * This is demo data, not installation data: DatabaseSeeder does not call it.
 * Run it explicitly on a local or staging copy when the real roster has not
 * been entered yet and the workflow must be walked end to end.
 *
 * Every account signs in with the configured demo password (development
 * fallback: "password") and holds both the
 * Employee role (so it can file a travel order) and its seat role (so it can
 * approve one). Office codes in the roster refer to the official directory
 * seeded by OfficeSeeder.
 *
 *   employee.engineer  Maria Clara Santos   MEO     files travel orders
 *   head.engineer      Pedro Bautista       MEO     Department Head
 *   deputy.engineer    Tomas Aquino         MEO     Department Head (deputy)
 *   employee.sbo       Grace Villanueva     SBO     files travel orders
 *   head.sbo           Carlo Mendoza        SBO     Department Head
 *   budget             Corazon Reyes        MBO     Budget Officer
 *   mayor              Rafael Mercado       MO      Mayor
 *   vicemayor          Liza Navarro         VMAYOR  Vice Mayor (Mayor role),
 *                                                 acting on the Mayor step
 *   hrmo               Ramon Bautista       HRMO    HRMO
 *
 * The engineering and Legislative offices are the two chains that can be
 * walked from submission to completion: they are the ones with department
 * head designations. The remaining seats exist so every approval stop has a
 * real person with a real account. The Vice Mayor holds the Mayor role because
 * the system has no separate Vice Mayor role; what changes the legislative
 * chain is the workflow, not the account's role.
 *
 * To show cross-office acting, the Vice Mayor is also a deputy holder of the
 * Mayor Approval step for the Mayor's Office. The two receive the step
 * together and the first to act claims it, the same rule as any other
 * multi-holder step.
 */
class DemoDataSeeder extends Seeder
{
    use ResolvesSeedCredentials;
    use WithoutModelEvents;

    /**
     * @var array<int, array{
     *     username: string,
     *     first_name: string,
     *     middle_name: string|null,
     *     last_name: string,
     *     email: string,
     *     office: string,
     *     position: string,
     *     contact_number: string,
     *     monthly_salary: string,
     *     roles: array<int, SystemRole>
     * }>
     */
    private const PEOPLE = [
        [
            'username' => 'employee.engineer',
            'first_name' => 'Maria Clara',
            'middle_name' => null,
            'last_name' => 'Santos',
            'email' => 'maria.santos@example.com',
            'office' => 'MEO',
            'position' => 'Administrative Officer',
            'contact_number' => '0917 100 0001',
            'monthly_salary' => '42000.00',
            'roles' => [SystemRole::Employee],
        ],
        [
            'username' => 'head.engineer',
            'first_name' => 'Pedro',
            'middle_name' => 'Reyes',
            'last_name' => 'Bautista',
            'email' => 'pedro.bautista@example.com',
            'office' => 'MEO',
            'position' => 'Municipal Engineer',
            'contact_number' => '0917 100 0002',
            'monthly_salary' => '78000.00',
            'roles' => [SystemRole::Employee, SystemRole::DepartmentHead],
        ],
        [
            'username' => 'deputy.engineer',
            'first_name' => 'Tomas',
            'middle_name' => 'Dizon',
            'last_name' => 'Aquino',
            'email' => 'tomas.aquino@example.com',
            'office' => 'MEO',
            'position' => 'Assistant Municipal Engineer',
            'contact_number' => '0917 100 0003',
            'monthly_salary' => '62000.00',
            'roles' => [SystemRole::Employee, SystemRole::DepartmentHead],
        ],
        [
            'username' => 'employee.sbo',
            'first_name' => 'Grace',
            'middle_name' => 'Lim',
            'last_name' => 'Villanueva',
            'email' => 'grace.villanueva@example.com',
            'office' => 'SBO',
            'position' => 'Administrative Officer',
            'contact_number' => '0917 100 0004',
            'monthly_salary' => '42000.00',
            'roles' => [SystemRole::Employee],
        ],
        [
            'username' => 'head.sbo',
            'first_name' => 'Carlo',
            'middle_name' => 'Reyes',
            'last_name' => 'Mendoza',
            'email' => 'carlo.mendoza@example.com',
            'office' => 'SBO',
            'position' => 'Sangguniang Bayan Secretary',
            'contact_number' => '0917 100 0005',
            'monthly_salary' => '72000.00',
            'roles' => [SystemRole::Employee, SystemRole::DepartmentHead],
        ],
        [
            'username' => 'budget',
            'first_name' => 'Corazon',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'email' => 'corazon.reyes@example.com',
            'office' => 'MBO',
            'position' => 'Municipal Budget Officer',
            'contact_number' => '0917 100 0006',
            'monthly_salary' => '76000.00',
            'roles' => [SystemRole::Employee, SystemRole::BudgetOfficer],
        ],
        [
            'username' => 'mayor',
            'first_name' => 'Rafael',
            'middle_name' => 'Cruz',
            'last_name' => 'Mercado',
            'email' => 'rafael.mercado@example.com',
            'office' => 'MO',
            'position' => 'Municipal Mayor',
            'contact_number' => '0917 100 0007',
            'monthly_salary' => '120000.00',
            'roles' => [SystemRole::Employee, SystemRole::Mayor],
        ],
        [
            'username' => 'vicemayor',
            'first_name' => 'Liza',
            'middle_name' => 'Garcia',
            'last_name' => 'Navarro',
            'email' => 'liza.navarro@example.com',
            'office' => 'VMAYOR',
            'position' => 'Municipal Vice Mayor',
            'contact_number' => '0917 100 0008',
            'monthly_salary' => '96000.00',
            'roles' => [SystemRole::Employee, SystemRole::Mayor],
        ],
        [
            'username' => 'hrmo',
            'first_name' => 'Ramon',
            'middle_name' => 'Flores',
            'last_name' => 'Bautista',
            'email' => 'ramon.bautista@example.com',
            'office' => 'HRMO',
            'position' => 'Human Resource Management Officer',
            'contact_number' => '0917 100 0009',
            'monthly_salary' => '74000.00',
            'roles' => [SystemRole::Employee, SystemRole::Hrmo],
        ],
    ];

    /**
     * Who answers each approval seat, keyed by office code and step type name.
     *
     * @var array<int, array{office: string, username: string, step: string, is_deputy: bool, is_acting?: bool}>
     */
    private const DESIGNATIONS = [
        ['office' => 'MEO', 'username' => 'head.engineer', 'step' => 'Department Head Approval', 'is_deputy' => false],
        ['office' => 'MEO', 'username' => 'deputy.engineer', 'step' => 'Department Head Approval', 'is_deputy' => true],
        ['office' => 'SBO', 'username' => 'head.sbo', 'step' => 'Department Head Approval', 'is_deputy' => false],
        ['office' => 'MBO', 'username' => 'budget', 'step' => 'Budget Review', 'is_deputy' => false],
        ['office' => 'MO', 'username' => 'mayor', 'step' => 'Mayor Approval', 'is_deputy' => false],
        ['office' => 'MO', 'username' => 'vicemayor', 'step' => 'Mayor Approval', 'is_deputy' => true, 'is_acting' => true],
        ['office' => 'VMAYOR', 'username' => 'vicemayor', 'step' => 'Vice Mayor Approval', 'is_deputy' => false],
        ['office' => 'HRMO', 'username' => 'hrmo', 'step' => 'HRMO Review', 'is_deputy' => false],
    ];

    /**
     * The sample per diem breakdown attached to the provincial destination.
     *
     * @var array<int, array{meal_type: string, name: string, amount: string, cutoff_time: string, sort_order: int}>
     */
    private const PER_DIEM_MEALS = [
        ['meal_type' => 'breakfast', 'name' => 'Breakfast', 'amount' => '80.00', 'cutoff_time' => '08:00', 'sort_order' => 1],
        ['meal_type' => 'lunch', 'name' => 'Lunch', 'amount' => '120.00', 'cutoff_time' => '12:00', 'sort_order' => 2],
        ['meal_type' => 'dinner', 'name' => 'Dinner', 'amount' => '120.00', 'cutoff_time' => '18:00', 'sort_order' => 3],
    ];

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            OfficeSeeder::class,
            SampleEmploymentAndPositionSeeder::class,
            WorkflowSeeder::class,
            TravelOptionsSeeder::class,
            LocationSeeder::class,
        ]);

        $offices = Office::query()
            ->whereIn('office_code', ['MEO', 'SBO', 'MBO', 'MO', 'VMAYOR', 'HRMO'])
            ->get()
            ->keyBy('office_code');

        $employmentType = EmploymentType::query()->firstOrCreate(
            ['name' => 'Regular'],
            ['description' => 'Permanent full-time employee.'],
        );

        $stepTypes = WorkflowStepType::query()
            ->whereIn('name', array_column(self::DESIGNATIONS, 'step'))
            ->get()
            ->keyBy('name');

        /** @var array<string, Employee> $employees */
        $employees = [];

        foreach (self::PEOPLE as $person) {
            $office = $offices->get($person['office']);

            if (! $office instanceof Office) {
                throw new RuntimeException(
                    'The demo office '.$person['office'].' is missing. Run OfficeSeeder first.'
                );
            }

            $position = Position::query()->firstOrCreate(
                ['name' => $person['position']],
                [
                    'description' => 'Demo position for '.$person['position'].'.',
                    'employment_type_id' => $employmentType->id,
                ],
            );

            $employee = Employee::query()->firstOrNew([
                'first_name' => $person['first_name'],
                'last_name' => $person['last_name'],
                'office_id' => $office->id,
            ]);

            $employee->fill([
                'middle_name' => $person['middle_name'],
                'position_id' => $position->id,
                'employment_type_id' => $employmentType->id,
                'contact_number' => $person['contact_number'],
                'monthly_salary' => $person['monthly_salary'],
                'is_active' => true,
            ])->save();

            $user = User::query()->firstOrNew(['username' => $person['username']]);

            $user->fill([
                'name' => $this->displayName($person),
                'email' => $person['email'],
            ]);

            if (! $user->exists) {
                // A demo account is verified because it exists to be signed in
                // to immediately; there is no mailbox to confirm.
                $user->password = Hash::make($this->seededPassword('seed-credentials.demo_password', 'DEMO_SEED_PASSWORD'));
                $user->must_change_password = $this->shouldForcePasswordChange();
                $user->email_verified_at = Carbon::now();
            }

            $user->employee_id = $employee->id;
            $user->save();

            $roles = Role::query()
                ->whereIn('slug', array_map(
                    fn (SystemRole $role): string => $role->value,
                    $person['roles'],
                ))
                ->get();

            $user->assignRole($roles->all());

            $employees[$person['username']] = $employee;
        }

        foreach (self::DESIGNATIONS as $definition) {
            $office = $offices->get($definition['office']);
            $employee = $employees[$definition['username']] ?? null;
            $stepType = $stepTypes->get($definition['step']);

            if (! $office instanceof Office || ! $employee instanceof Employee || ! $stepType instanceof WorkflowStepType) {
                throw new RuntimeException(
                    'The demo designation for '.$definition['username'].' could not be resolved.'
                );
            }

            OfficeDesignation::query()->firstOrCreate(
                [
                    'office_id' => $office->id,
                    'workflow_step_type_id' => $stepType->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'is_deputy' => $definition['is_deputy'],
                    'is_active' => true,
                    'starts_at' => ($definition['is_acting'] ?? false)
                        ? now()->startOfYear()->toDateString()
                        : null,
                ],
            );
        }

        $this->seedPerDiem();
    }

    /**
     * A printable name for the account, matching the employee record.
     *
     * @param  array{first_name: string, middle_name: string|null, last_name: string}  $person
     */
    private function displayName(array $person): string
    {
        return $person['middle_name'] === null
            ? $person['first_name'].' '.$person['last_name']
            : $person['first_name'].' '.$person['middle_name'].' '.$person['last_name'];
    }

    /**
     * One sample per diem configuration with its meal breakdown.
     */
    private function seedPerDiem(): void
    {
        $location = Location::query()->firstOrCreate(
            ['name' => 'Provincial Capitol'],
            ['is_active' => true],
        );

        $configuration = PerDiemConfiguration::query()->firstOrCreate(
            ['name' => 'Demo Standard Per Diem'],
            [
                'effective_from' => now()->startOfYear()->toDateString(),
                'daily_rate' => '320.00',
                'is_active' => true,
            ],
        );

        $configuration->locations()->syncWithoutDetaching([$location->id]);

        foreach (self::PER_DIEM_MEALS as $meal) {
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
    }
}
