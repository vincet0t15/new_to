<?php

use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Create an account holding the given permissions, with the administration
 * gate every position route requires added automatically.
 *
 * @param  array<int, string>  $permissions
 */
function positionUser(array $permissions = ['positions.view']): User
{
    $permissions = array_values(array_unique(['administration.view', ...$permissions]));

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('guests are redirected to the login page', function () {
    $this->get(route('positions.index'))
        ->assertRedirect(route('login'));
});

test('accounts without the administration gate cannot list positions', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('positions.index'))
        ->assertForbidden();
});

test('accounts with the view permission can list positions', function () {
    $employmentType = EmploymentType::factory()->create(['name' => 'Permanent']);
    Position::factory()->create([
        'name' => 'Administrative Officer',
        'description' => 'Handles records',
        'employment_type_id' => $employmentType->id,
    ]);

    $this->actingAs(positionUser())
        ->get(route('positions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('positions/index')
            ->has('positions.data', 1)
            ->where('positions.data.0.name', 'Administrative Officer')
            ->where('positions.data.0.employment_type.name', 'Permanent')
            ->where('employmentTypes.0.name', 'Permanent')
            ->where('filters.search', '')
        );
});

test('positions can be searched by name or description', function () {
    Position::factory()->create(['name' => 'Administrative Officer']);
    Position::factory()->create([
        'name' => 'Accountant',
        'description' => 'Prepares financial reports',
    ]);

    $this->actingAs(positionUser())
        ->get(route('positions.index', ['search' => 'financial']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('positions.data', 1)
            ->where('positions.data.0.name', 'Accountant')
            ->where('filters.search', 'financial')
        );
});

test('the position list paginates at fifty rows', function () {
    Position::factory()->count(51)->create();

    $this->actingAs(positionUser())
        ->get(route('positions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('positions.data', 50)
            ->where('positions.total', 51)
            ->where('positions.last_page', 2)
        );
});

test('a position can be created with an employment type', function () {
    $employmentType = EmploymentType::factory()->create(['name' => 'Permanent']);

    $this->actingAs(positionUser(['positions.create']))
        ->post(route('positions.store'), [
            'name' => 'Planning Officer',
            'description' => 'Prepares development plans',
            'employment_type_id' => $employmentType->id,
        ])
        ->assertRedirect();

    $position = Position::query()->where('name', 'Planning Officer')->sole();

    expect($position->employment_type_id)->toBe($employmentType->id)
        ->and($position->description)->toBe('Prepares development plans');
});

test('a position can be created without an employment type', function () {
    $this->actingAs(positionUser(['positions.create']))
        ->post(route('positions.store'), [
            'name' => 'Consultant',
            'description' => '',
            'employment_type_id' => '',
        ])
        ->assertRedirect();

    $position = Position::query()->where('name', 'Consultant')->sole();

    expect($position->employment_type_id)->toBeNull();
});

test('creating a position rejects an unknown employment type', function () {
    $this->actingAs(positionUser(['positions.create']))
        ->post(route('positions.store'), [
            'name' => 'Planning Officer',
            'employment_type_id' => 999,
        ])
        ->assertSessionHasErrors('employment_type_id');

    expect(Position::query()->count())->toBe(0);
});

test('creating a position requires a name', function () {
    $this->actingAs(positionUser(['positions.create']))
        ->post(route('positions.store'), [
            'name' => '',
            'description' => 'Missing name',
        ])
        ->assertSessionHasErrors('name');

    expect(Position::query()->count())->toBe(0);
});

test('position names must be unique', function () {
    Position::factory()->create(['name' => 'Administrative Officer']);

    $this->actingAs(positionUser(['positions.create']))
        ->post(route('positions.store'), [
            'name' => 'Administrative Officer',
        ])
        ->assertSessionHasErrors('name');

    expect(Position::query()->count())->toBe(1);
});

test('creating a position requires the create permission', function () {
    $this->actingAs(positionUser())
        ->post(route('positions.store'), [
            'name' => 'Planning Officer',
        ])
        ->assertForbidden();

    expect(Position::query()->count())->toBe(0);
});

test('a position can be updated', function () {
    $position = Position::factory()->create([
        'name' => 'Administrative Officer',
        'description' => 'Old description',
    ]);
    $employmentType = EmploymentType::factory()->create(['name' => 'Contractual']);

    $this->actingAs(positionUser(['positions.update']))
        ->put(route('positions.update', $position), [
            'name' => 'Senior Administrative Officer',
            'description' => 'New description',
            'employment_type_id' => $employmentType->id,
        ])
        ->assertRedirect();

    $position->refresh();

    expect($position->name)->toBe('Senior Administrative Officer')
        ->and($position->description)->toBe('New description')
        ->and($position->employment_type_id)->toBe($employmentType->id);
});

test('a position may keep its own name when updated', function () {
    $position = Position::factory()->create([
        'name' => 'Administrative Officer',
        'description' => 'Old description',
    ]);

    $this->actingAs(positionUser(['positions.update']))
        ->put(route('positions.update', $position), [
            'name' => 'Administrative Officer',
            'description' => 'Updated description',
        ])
        ->assertSessionHasNoErrors();

    expect($position->refresh()->description)->toBe('Updated description');
});

test('a position cannot take another position name on update', function () {
    Position::factory()->create(['name' => 'Administrative Officer']);
    $position = Position::factory()->create(['name' => 'Accountant']);

    $this->actingAs(positionUser(['positions.update']))
        ->put(route('positions.update', $position), [
            'name' => 'Administrative Officer',
        ])
        ->assertSessionHasErrors('name');

    expect($position->refresh()->name)->toBe('Accountant');
});

test('updating a position requires the update permission', function () {
    $position = Position::factory()->create(['name' => 'Administrative Officer']);

    $this->actingAs(positionUser())
        ->put(route('positions.update', $position), [
            'name' => 'Changed',
        ])
        ->assertForbidden();

    expect($position->refresh()->name)->toBe('Administrative Officer');
});

test('a position can be deleted', function () {
    $position = Position::factory()->create();

    $this->actingAs(positionUser(['positions.delete']))
        ->delete(route('positions.destroy', $position))
        ->assertRedirect();

    $this->assertModelMissing($position);
});

test('a position still held by an employee cannot be deleted', function () {
    $position = Position::factory()->create();
    Employee::factory()->create(['position_id' => $position->id]);

    $this->actingAs(positionUser(['positions.delete']))
        ->delete(route('positions.destroy', $position))
        ->assertRedirect();

    $this->assertModelExists($position);
});

test('deleting a position requires the delete permission', function () {
    $position = Position::factory()->create();

    $this->actingAs(positionUser())
        ->delete(route('positions.destroy', $position))
        ->assertForbidden();

    $this->assertModelExists($position);
});
