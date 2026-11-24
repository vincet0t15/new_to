<?php

use App\Enums\SystemRole;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\PerDiemConfiguration;
use App\Models\PerDiemMealRule;
use App\Models\User;
use App\Services\WorkflowRouteResolver;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

test('the demo roster is seeded once and survives a rerun', function () {
    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    expect(Employee::query()->count())->toBe(9)
        ->and(User::query()->count())->toBe(9)
        ->and(OfficeDesignation::query()->count())->toBe(8)
        ->and(PerDiemConfiguration::query()->count())->toBe(1)
        ->and(PerDiemMealRule::query()->count())->toBe(3);
});

test('every demo account can sign in, file, and hold its seat role', function () {
    $this->seed(DemoDataSeeder::class);

    $expectedRoles = [
        'employee.engineer' => [SystemRole::Employee],
        'head.engineer' => [SystemRole::Employee, SystemRole::DepartmentHead],
        'deputy.engineer' => [SystemRole::Employee, SystemRole::DepartmentHead],
        'employee.sbo' => [SystemRole::Employee],
        'head.sbo' => [SystemRole::Employee, SystemRole::DepartmentHead],
        'budget' => [SystemRole::Employee, SystemRole::BudgetOfficer],
        'mayor' => [SystemRole::Employee, SystemRole::Mayor],
        'vicemayor' => [SystemRole::Employee, SystemRole::Mayor],
        'hrmo' => [SystemRole::Employee, SystemRole::Hrmo],
    ];

    foreach ($expectedRoles as $username => $roles) {
        $user = User::query()->where('username', $username)->sole();

        expect($user->employee)->not->toBeNull()
            ->and($user->email_verified_at)->not->toBeNull()
            ->and(Hash::check('password', $user->password))->toBeTrue();

        foreach ($roles as $role) {
            expect($user->hasRoleSlug($role->value))->toBeTrue();
        }
    }

    $this->post(route('login.store'), [
        'username' => 'employee.engineer',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs(
        User::query()->where('username', 'employee.engineer')->sole()
    );
});

test('demo credentials and the first-login flag can be configured', function () {
    config()->set('seed-credentials.demo_password', 'configured-secret');
    config()->set('seed-credentials.force_password_change', true);

    $this->seed(DemoDataSeeder::class);

    $user = User::query()->where('username', 'employee.engineer')->sole();

    expect(Hash::check('configured-secret', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue();
});

test('the engineering chain resolves to its head and deputy', function () {
    $this->seed(DemoDataSeeder::class);

    $office = Office::query()->where('office_code', 'MEO')->sole();
    $preview = app(WorkflowRouteResolver::class)->preview($office);

    expect($preview['is_ready'])->toBeTrue()
        ->and($preview['uses_override'])->toBeFalse()
        ->and(array_column($preview['steps'], 'label'))->toBe([
            'Department Head Approval',
            'Budget Review',
            'Mayor Approval',
            'HRMO Review',
        ])
        ->and($preview['steps'][0]['designees'])->toHaveCount(2)
        ->and($preview['steps'][0]['designees'][0]['name'])->toBe('Pedro Reyes Bautista')
        ->and($preview['steps'][0]['designees'][0]['is_deputy'])->toBeFalse()
        ->and($preview['steps'][0]['designees'][1]['name'])->toBe('Tomas Dizon Aquino')
        ->and($preview['steps'][0]['designees'][1]['is_deputy'])->toBeTrue()
        ->and($preview['steps'][1]['designees'][0]['name'])->toBe('Corazon Santos Reyes')
        ->and($preview['steps'][2]['designees'])->toHaveCount(2)
        ->and($preview['steps'][2]['designees'][0]['name'])->toBe('Rafael Cruz Mercado')
        ->and($preview['steps'][2]['designees'][1]['name'])->toBe('Liza Garcia Navarro')
        ->and($preview['steps'][2]['designees'][1]['is_deputy'])->toBeTrue()
        ->and($preview['steps'][3]['designees'][0]['name'])->toBe('Ramon Flores Bautista');
});

test('the legislative chain ends at the vice mayor', function () {
    $this->seed(DemoDataSeeder::class);

    $office = Office::query()->where('office_code', 'SBO')->sole();
    $preview = app(WorkflowRouteResolver::class)->preview($office);

    expect($preview['is_ready'])->toBeTrue()
        ->and($preview['uses_override'])->toBeTrue()
        ->and(array_column($preview['steps'], 'label'))->toBe([
            'Department Head Approval',
            'Budget Review',
            'Vice Mayor Approval',
            'HRMO Review',
        ])
        ->and($preview['steps'][0]['designees'][0]['name'])->toBe('Carlo Reyes Mendoza')
        ->and($preview['steps'][2]['designees'][0]['name'])->toBe('Liza Garcia Navarro');
});

test('the sample per diem covers the provincial destination', function () {
    $this->seed(DemoDataSeeder::class);

    $configuration = PerDiemConfiguration::query()
        ->where('name', 'Demo Standard Per Diem')
        ->sole();

    $location = Location::query()->where('name', 'Provincial Capitol')->sole();

    expect($configuration->locations()->whereKey($location->id)->exists())->toBeTrue()
        ->and($configuration->perDiemMealRules)->toHaveCount(3)
        ->and($configuration->perDiemMealRules->pluck('amount')->map(
            fn (mixed $amount): string => (string) $amount
        )->all())->toBe(['80.00', '120.00', '120.00'])
        ->and($configuration->perDiemMealRules->pluck('cutoff_time')->map(
            fn (mixed $time): string => Carbon::parse((string) $time)->format('H:i')
        )->all())->toBe(['08:00', '12:00', '18:00']);
});
