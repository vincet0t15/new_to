<?php

use App\Models\Employee;
use App\Models\Office;
use App\Models\Permission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Create an account holding the given permissions, with the administration
 * gate every office route requires added automatically.
 *
 * @param  array<int, string>  $permissions
 */
function officeUser(array $permissions = ['offices.view']): User
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
    $response = $this->get(route('offices.index'));

    $response->assertRedirect(route('login'));
});

test('accounts without the administration gate cannot list offices', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('offices.index'))
        ->assertForbidden();
});

test('accounts with the view permission can list offices', function () {
    Office::factory()->create(['office_name' => 'City Administrator']);

    $this->actingAs(officeUser())
        ->get(route('offices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('offices/index')
            ->has('offices.data', 1)
            ->where('offices.data.0.office_name', 'City Administrator')
            ->where('filters.search', '')
        );
});

test('offices can be searched by name or code', function () {
    Office::factory()->create([
        'office_name' => 'Accounting Office',
        'office_code' => 'ACC',
    ]);
    Office::factory()->create([
        'office_name' => 'Health Office',
        'office_code' => 'HLTH',
    ]);

    $this->actingAs(officeUser())
        ->get(route('offices.index', ['search' => 'HLTH']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('offices.data', 1)
            ->where('offices.data.0.office_name', 'Health Office')
            ->where('filters.search', 'HLTH')
        );
});

test('the office list paginates at fifty rows', function () {
    Office::factory()->count(51)->create();

    $this->actingAs(officeUser())
        ->get(route('offices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('offices.data', 50)
            ->where('offices.total', 51)
            ->where('offices.last_page', 2)
        );
});

test('an office can be created', function () {
    $this->actingAs(officeUser(['offices.create']))
        ->post(route('offices.store'), [
            'office_name' => 'Mayor\'s Office',
            'office_code' => 'MO',
        ])
        ->assertRedirect();

    $office = Office::query()->where('office_name', 'Mayor\'s Office')->sole();

    expect($office->office_code)->toBe('MO');
});

test('creating an office requires a name', function () {
    $this->actingAs(officeUser(['offices.create']))
        ->post(route('offices.store'), [
            'office_name' => '',
            'office_code' => '',
        ])
        ->assertSessionHasErrors('office_name');

    expect(Office::query()->count())->toBe(0);
});

test('office names must be unique', function () {
    Office::factory()->create(['office_name' => 'Budget Office']);

    $this->actingAs(officeUser(['offices.create']))
        ->post(route('offices.store'), [
            'office_name' => 'Budget Office',
        ])
        ->assertSessionHasErrors('office_name');

    expect(Office::query()->count())->toBe(1);
});

test('creating an office requires the create permission', function () {
    $this->actingAs(officeUser())
        ->post(route('offices.store'), [
            'office_name' => 'Mayor\'s Office',
        ])
        ->assertForbidden();

    expect(Office::query()->count())->toBe(0);
});

test('an office can be updated', function () {
    $office = Office::factory()->create([
        'office_name' => 'Old Name',
        'office_code' => 'OLD',
    ]);

    $this->actingAs(officeUser(['offices.update']))
        ->put(route('offices.update', $office), [
            'office_name' => 'New Name',
            'office_code' => 'NEW',
        ])
        ->assertRedirect();

    $office->refresh();

    expect($office->office_name)->toBe('New Name')
        ->and($office->office_code)->toBe('NEW');
});

test('an office may keep its own name when updated', function () {
    $office = Office::factory()->create([
        'office_name' => 'Budget Office',
        'office_code' => 'BO',
    ]);

    $this->actingAs(officeUser(['offices.update']))
        ->put(route('offices.update', $office), [
            'office_name' => 'Budget Office',
            'office_code' => 'BUD',
        ])
        ->assertSessionHasNoErrors();

    expect($office->refresh()->office_code)->toBe('BUD');
});

test('an office cannot take another office name on update', function () {
    Office::factory()->create(['office_name' => 'Budget Office']);
    $office = Office::factory()->create(['office_name' => 'Health Office']);

    $this->actingAs(officeUser(['offices.update']))
        ->put(route('offices.update', $office), [
            'office_name' => 'Budget Office',
        ])
        ->assertSessionHasErrors('office_name');

    expect($office->refresh()->office_name)->toBe('Health Office');
});

test('updating an office requires the update permission', function () {
    $office = Office::factory()->create(['office_name' => 'Health Office']);

    $this->actingAs(officeUser())
        ->put(route('offices.update', $office), [
            'office_name' => 'Changed',
        ])
        ->assertForbidden();

    expect($office->refresh()->office_name)->toBe('Health Office');
});

test('an office can be deleted', function () {
    $office = Office::factory()->create();

    $this->actingAs(officeUser(['offices.delete']))
        ->delete(route('offices.destroy', $office))
        ->assertRedirect();

    $this->assertModelMissing($office);
});

test('an office that still has employees cannot be deleted', function () {
    $office = Office::factory()->create();
    Employee::factory()->create(['office_id' => $office->id]);

    $this->actingAs(officeUser(['offices.delete']))
        ->delete(route('offices.destroy', $office))
        ->assertRedirect();

    $this->assertModelExists($office);
});

test('deleting an office requires the delete permission', function () {
    $office = Office::factory()->create();

    $this->actingAs(officeUser())
        ->delete(route('offices.destroy', $office))
        ->assertForbidden();

    $this->assertModelExists($office);
});
