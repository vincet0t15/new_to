<?php

use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Create an account holding the given permissions, with the administration
 * gate every employment type route requires added automatically.
 *
 * @param  array<int, string>  $permissions
 */
function employmentTypeUser(array $permissions = ['employment-types.view']): User
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

test('guests are redirected to the login page', function () {
    $this->get(route('employment-types.index'))
        ->assertRedirect(route('login'));
});

test('accounts without the administration gate cannot list employment types', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('employment-types.index'))
        ->assertForbidden();
});

test('accounts with the view permission can list employment types', function () {
    $employmentType = EmploymentType::factory()->create([
        'name' => 'Permanent',
        'description' => 'Regular plantilla personnel',
    ]);
    Position::factory()->count(2)->create([
        'employment_type_id' => $employmentType->id,
    ]);

    $this->actingAs(employmentTypeUser())
        ->get(route('employment-types.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employment-types/index')
            ->has('employmentTypes.data', 1)
            ->where('employmentTypes.data.0.name', 'Permanent')
            ->where('employmentTypes.data.0.positions_count', 2)
            ->where('filters.search', '')
        );
});

test('employment types can be searched by name or description', function () {
    EmploymentType::factory()->create([
        'name' => 'Permanent',
        'description' => 'Regular plantilla personnel',
    ]);
    EmploymentType::factory()->create([
        'name' => 'Contractual',
        'description' => 'Project-based engagement',
    ]);

    $this->actingAs(employmentTypeUser())
        ->get(route('employment-types.index', ['search' => 'project-based']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('employmentTypes.data', 1)
            ->where('employmentTypes.data.0.name', 'Contractual')
            ->where('filters.search', 'project-based')
        );
});

test('the employment type list paginates at fifty rows', function () {
    EmploymentType::factory()->count(51)->create();

    $this->actingAs(employmentTypeUser())
        ->get(route('employment-types.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('employmentTypes.data', 50)
            ->where('employmentTypes.total', 51)
            ->where('employmentTypes.last_page', 2)
        );
});

test('an employment type can be created', function () {
    $this->actingAs(employmentTypeUser(['employment-types.create']))
        ->post(route('employment-types.store'), [
            'name' => 'Casual',
            'description' => 'Occasional city personnel',
        ])
        ->assertRedirect();

    $employmentType = EmploymentType::query()->where('name', 'Casual')->sole();

    expect($employmentType->description)->toBe('Occasional city personnel');
});

test('creating an employment type requires a name', function () {
    $this->actingAs(employmentTypeUser(['employment-types.create']))
        ->post(route('employment-types.store'), [
            'name' => '',
            'description' => 'Missing name',
        ])
        ->assertSessionHasErrors('name');

    expect(EmploymentType::query()->count())->toBe(0);
});

test('employment type names must be unique', function () {
    EmploymentType::factory()->create(['name' => 'Permanent']);

    $this->actingAs(employmentTypeUser(['employment-types.create']))
        ->post(route('employment-types.store'), [
            'name' => 'Permanent',
        ])
        ->assertSessionHasErrors('name');

    expect(EmploymentType::query()->count())->toBe(1);
});

test('creating an employment type requires the create permission', function () {
    $this->actingAs(employmentTypeUser())
        ->post(route('employment-types.store'), [
            'name' => 'Casual',
        ])
        ->assertForbidden();

    expect(EmploymentType::query()->count())->toBe(0);
});

test('an employment type can be updated', function () {
    $employmentType = EmploymentType::factory()->create([
        'name' => 'Permanent',
        'description' => 'Old description',
    ]);

    $this->actingAs(employmentTypeUser(['employment-types.update']))
        ->put(route('employment-types.update', $employmentType), [
            'name' => 'Regular',
            'description' => 'New description',
        ])
        ->assertRedirect();

    $employmentType->refresh();

    expect($employmentType->name)->toBe('Regular')
        ->and($employmentType->description)->toBe('New description');
});

test('an employment type may keep its own name when updated', function () {
    $employmentType = EmploymentType::factory()->create([
        'name' => 'Permanent',
        'description' => 'Old description',
    ]);

    $this->actingAs(employmentTypeUser(['employment-types.update']))
        ->put(route('employment-types.update', $employmentType), [
            'name' => 'Permanent',
            'description' => 'Updated description',
        ])
        ->assertSessionHasNoErrors();

    expect($employmentType->refresh()->description)->toBe('Updated description');
});

test('an employment type cannot take another employment type name on update', function () {
    EmploymentType::factory()->create(['name' => 'Permanent']);
    $employmentType = EmploymentType::factory()->create(['name' => 'Contractual']);

    $this->actingAs(employmentTypeUser(['employment-types.update']))
        ->put(route('employment-types.update', $employmentType), [
            'name' => 'Permanent',
        ])
        ->assertSessionHasErrors('name');

    expect($employmentType->refresh()->name)->toBe('Contractual');
});

test('updating an employment type requires the update permission', function () {
    $employmentType = EmploymentType::factory()->create(['name' => 'Permanent']);

    $this->actingAs(employmentTypeUser())
        ->put(route('employment-types.update', $employmentType), [
            'name' => 'Changed',
        ])
        ->assertForbidden();

    expect($employmentType->refresh()->name)->toBe('Permanent');
});

test('an employment type can be deleted', function () {
    $employmentType = EmploymentType::factory()->create();

    $this->actingAs(employmentTypeUser(['employment-types.delete']))
        ->delete(route('employment-types.destroy', $employmentType))
        ->assertRedirect();

    $this->assertModelMissing($employmentType);
});

test('an employment type still used by a position cannot be deleted', function () {
    $employmentType = EmploymentType::factory()->create();
    Position::factory()->create(['employment_type_id' => $employmentType->id]);

    $this->actingAs(employmentTypeUser(['employment-types.delete']))
        ->delete(route('employment-types.destroy', $employmentType))
        ->assertRedirect();

    $this->assertModelExists($employmentType);
});

test('an employment type still used by an employee cannot be deleted', function () {
    $employmentType = EmploymentType::factory()->create();
    Employee::factory()->create(['employment_type_id' => $employmentType->id]);

    $this->actingAs(employmentTypeUser(['employment-types.delete']))
        ->delete(route('employment-types.destroy', $employmentType))
        ->assertRedirect();

    $this->assertModelExists($employmentType);
});

test('deleting an employment type requires the delete permission', function () {
    $employmentType = EmploymentType::factory()->create();

    $this->actingAs(employmentTypeUser())
        ->delete(route('employment-types.destroy', $employmentType))
        ->assertForbidden();

    $this->assertModelExists($employmentType);
});
