<?php

use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\EmployeeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Create an account holding the given permissions, with the administration
 * gate every employee route requires added automatically.
 *
 * @param  array<int, string>  $permissions
 */
function employeeUser(array $permissions = ['employees.view']): User
{
    $permissions = array_values(array_unique(['administration.view', ...$permissions]));

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    grantAdminPortal($user);
    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * The reference data an employee needs so the form can be submitted.
 *
 * @return array{office: Office, position: Position, employment_type: EmploymentType}
 */
function employeeLookups(): array
{
    return [
        'office' => Office::factory()->create(['office_name' => 'Budget Office']),
        'position' => Position::factory()->create(['name' => 'Accountant']),
        'employment_type' => EmploymentType::factory()->create(['name' => 'Permanent']),
    ];
}

test('employee seeder fills the database to at least ten employees without duplicating on rerun', function () {
    Employee::factory()->count(2)->create();

    app(EmployeeSeeder::class)->run();
    app(EmployeeSeeder::class)->run();

    expect(Employee::query()->count())->toBe(10);
});

test('guests are redirected to the login page', function () {
    $this->get(route('employees.index'))
        ->assertRedirect(route('login'));
});

test('accounts without the administration gate cannot list employees', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('employees.index'))
        ->assertForbidden();
});

test('accounts with the view permission can list employees', function () {
    $lookups = employeeLookups();

    Employee::factory()->create([
        'first_name' => 'Maria',
        'middle_name' => 'Santos',
        'last_name' => 'Cruz',
        'suffix' => null,
        'office_id' => $lookups['office']->id,
        'position_id' => $lookups['position']->id,
        'employment_type_id' => $lookups['employment_type']->id,
    ]);

    $this->actingAs(employeeUser())
        ->get(route('employees.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/index')
            ->has('employees.data', 1)
            ->where('employees.data.0.full_name', 'Maria Santos Cruz')
            ->where('employees.data.0.office.office_name', 'Budget Office')
            ->where('employees.data.0.position.name', 'Accountant')
            ->where('employees.data.0.employment_type.name', 'Permanent')
            ->where('filters.search', '')
        );
});

test('employees can be searched by name, position, or office', function () {
    $lookups = employeeLookups();

    Employee::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Cruz',
        'office_id' => $lookups['office']->id,
        'position_id' => $lookups['position']->id,
        'employment_type_id' => $lookups['employment_type']->id,
    ]);
    Employee::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $this->actingAs(employeeUser())
        ->get(route('employees.index', ['search' => 'Accountant']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('employees.data', 1)
            ->where('employees.data.0.first_name', 'Maria')
        );

    $this->actingAs(employeeUser())
        ->get(route('employees.index', ['search' => 'Budget Office']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('employees.data', 1)
            ->where('employees.data.0.first_name', 'Maria')
        );
});

test('the employee list paginates at fifty rows', function () {
    Employee::factory()->count(51)->create();

    $this->actingAs(employeeUser())
        ->get(route('employees.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('employees.data', 50)
            ->where('employees.total', 51)
            ->where('employees.last_page', 2)
        );
});

test('the create page sends the reference data the form needs', function () {
    $lookups = employeeLookups();

    $this->actingAs(employeeUser(['employees.create']))
        ->get(route('employees.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/create')
            ->where('offices', fn ($offices): bool => collect($offices)->contains('office_name', 'Budget Office'))
            ->where('positions', fn ($positions): bool => collect($positions)->contains('name', 'Accountant'))
            ->where('employmentTypes', fn ($types): bool => collect($types)->contains('name', 'Permanent'))
        );
});

test('an employee can be created with a photo', function () {
    Storage::fake('public');
    $lookups = employeeLookups();

    $this->actingAs(employeeUser(['employees.create']))
        ->post(route('employees.store'), [
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Cruz',
            'suffix' => '',
            'position_id' => $lookups['position']->id,
            'office_id' => $lookups['office']->id,
            'employment_type_id' => $lookups['employment_type']->id,
            'contact_number' => '09171234567',
            'monthly_salary' => '25000.00',
            'is_active' => true,
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])
        ->assertRedirect(route('employees.index'));

    $employee = Employee::query()->where('first_name', 'Maria')->sole();

    expect($employee->full_name)->toBe('Maria Santos Cruz')
        ->and($employee->office_id)->toBe($lookups['office']->id)
        ->and($employee->position_id)->toBe($lookups['position']->id)
        ->and($employee->monthly_salary)->toBe('25000.00')
        ->and($employee->is_active)->toBeTrue()
        ->and($employee->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($employee->image_path);
});

test('creating an employee requires a name and reference data', function () {
    $this->actingAs(employeeUser(['employees.create']))
        ->post(route('employees.store'), [
            'first_name' => '',
            'last_name' => '',
            'position_id' => '',
            'office_id' => '',
            'employment_type_id' => '',
        ])
        ->assertSessionHasErrors([
            'first_name',
            'last_name',
            'position_id',
            'office_id',
            'employment_type_id',
        ]);

    expect(Employee::query()->count())->toBe(0);
});

test('creating an employee rejects an unknown office', function () {
    $lookups = employeeLookups();

    $this->actingAs(employeeUser(['employees.create']))
        ->post(route('employees.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Cruz',
            'position_id' => $lookups['position']->id,
            'office_id' => 999,
            'employment_type_id' => $lookups['employment_type']->id,
        ])
        ->assertSessionHasErrors('office_id');

    expect(Employee::query()->count())->toBe(0);
});

test('creating an employee requires the create permission', function () {
    $lookups = employeeLookups();

    $this->actingAs(employeeUser())
        ->post(route('employees.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Cruz',
            'position_id' => $lookups['position']->id,
            'office_id' => $lookups['office']->id,
            'employment_type_id' => $lookups['employment_type']->id,
        ])
        ->assertForbidden();

    expect(Employee::query()->count())->toBe(0);
});

test('the edit page sends the employee and reference data', function () {
    $lookups = employeeLookups();
    $employee = Employee::factory()->create([
        'first_name' => 'Maria',
        'middle_name' => null,
        'last_name' => 'Cruz',
        'suffix' => null,
        'office_id' => $lookups['office']->id,
        'position_id' => $lookups['position']->id,
        'employment_type_id' => $lookups['employment_type']->id,
    ]);

    $this->actingAs(employeeUser(['employees.update']))
        ->get(route('employees.edit', $employee))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/edit')
            ->where('employee.id', $employee->id)
            ->where('employee.full_name', 'Maria Cruz')
            ->where('offices', fn ($offices): bool => collect($offices)->contains('office_name', 'Budget Office'))
            ->where('positions', fn ($positions): bool => collect($positions)->contains('name', 'Accountant'))
            ->where('employmentTypes', fn ($types): bool => collect($types)->contains('name', 'Permanent'))
        );
});

test('an employee can be updated', function () {
    $employee = Employee::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Cruz',
        'is_active' => true,
    ]);

    $this->actingAs(employeeUser(['employees.update']))
        ->put(route('employees.update', $employee), [
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Cruz',
            'suffix' => '',
            'position_id' => $employee->position_id,
            'office_id' => $employee->office_id,
            'employment_type_id' => $employee->employment_type_id,
            'contact_number' => '09998887777',
            'monthly_salary' => '30000.00',
            'is_active' => false,
        ])
        ->assertRedirect(route('employees.index'));

    $employee->refresh();

    expect($employee->full_name)->toBe('Maria Santos Cruz')
        ->and($employee->contact_number)->toBe('09998887777')
        ->and($employee->monthly_salary)->toBe('30000.00')
        ->and($employee->is_active)->toBeFalse();
});

test('updating an employee replaces the old photo', function () {
    Storage::fake('public');

    $oldPath = UploadedFile::fake()
        ->image('old-photo.jpg')
        ->store('employees', 'public');
    $employee = Employee::factory()->create(['image_path' => $oldPath]);

    $this->actingAs(employeeUser(['employees.update']))
        ->put(route('employees.update', $employee), [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'position_id' => $employee->position_id,
            'office_id' => $employee->office_id,
            'employment_type_id' => $employee->employment_type_id,
            'is_active' => $employee->is_active,
            'photo' => UploadedFile::fake()->image('new-photo.jpg'),
        ])
        ->assertRedirect(route('employees.index'));

    $employee->refresh();

    expect($employee->image_path)->not->toBe($oldPath);

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($employee->image_path);
});

test('updating an employee can remove the current photo', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()
        ->image('photo.jpg')
        ->store('employees', 'public');
    $employee = Employee::factory()->create(['image_path' => $path]);

    $this->actingAs(employeeUser(['employees.update']))
        ->put(route('employees.update', $employee), [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'position_id' => $employee->position_id,
            'office_id' => $employee->office_id,
            'employment_type_id' => $employee->employment_type_id,
            'is_active' => $employee->is_active,
            'remove_photo' => true,
        ])
        ->assertRedirect(route('employees.index'));

    expect($employee->refresh()->image_path)->toBeNull();

    Storage::disk('public')->assertMissing($path);
});

test('updating an employee requires the update permission', function () {
    $employee = Employee::factory()->create(['first_name' => 'Maria']);

    $this->actingAs(employeeUser())
        ->put(route('employees.update', $employee), [
            'first_name' => 'Changed',
            'last_name' => 'Cruz',
            'position_id' => $employee->position_id,
            'office_id' => $employee->office_id,
            'employment_type_id' => $employee->employment_type_id,
        ])
        ->assertForbidden();

    expect($employee->refresh()->first_name)->toBe('Maria');
});

test('an employee can be deleted', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(employeeUser(['employees.delete']))
        ->delete(route('employees.destroy', $employee))
        ->assertRedirect();

    $this->assertModelMissing($employee);
});

test('an employee with a login account cannot be deleted', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->create();
    $user->employee_id = $employee->id;
    $user->save();

    $this->actingAs(employeeUser(['employees.delete']))
        ->delete(route('employees.destroy', $employee))
        ->assertRedirect();

    $this->assertModelExists($employee);
});

test('deleting an employee requires the delete permission', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(employeeUser())
        ->delete(route('employees.destroy', $employee))
        ->assertForbidden();

    $this->assertModelExists($employee);
});
