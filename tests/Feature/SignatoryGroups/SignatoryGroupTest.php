<?php

use App\Enums\SystemRole;
use App\Models\Employee;
use App\Models\ItinerarySignatoryGroup;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SignatoryGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * An account holding the given permissions, with the administration gate every
 * signatory route requires added automatically.
 *
 * @param  array<int, string>  $permissions
 */
function signatoryAdmin(array $permissions = ['signatory-groups.view']): User
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
    $this->get(route('signatory-groups.index'))
        ->assertRedirect(route('login'));
});

test('accounts without the administration gate cannot list signatory groups', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('signatory-groups.index'))
        ->assertForbidden();
});

test('accounts without the signatory view permission cannot list groups', function () {
    $user = signatoryAdmin(['administration.view']);

    // The seeded Admin role grants every permission, so the gate is exercised
    // by taking this one away from the role the portal already admits.
    Role::query()
        ->where('slug', SystemRole::Admin->value)
        ->sole()
        ->revokePermissionTo('signatory-groups.view');

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user)
        ->get(route('signatory-groups.index'))
        ->assertForbidden();
});

test('the list names each signer and the offices that print the group', function () {
    $certified = Employee::factory()->create([
        'first_name' => 'Maria',
        'middle_name' => null,
        'last_name' => 'Santos',
        'suffix' => null,
    ]);
    $office = Office::factory()->create(['office_name' => 'Municipal Budget Office']);

    $group = SignatoryGroup::create([
        'name' => 'Budget Office Signatories',
        'certified_signatory_employee_id' => $certified->id,
        'certified_signatory_title' => 'Municipal Budget Officer',
    ]);

    $office->update(['signatory_group_id' => $group->id]);

    $this->actingAs(signatoryAdmin())
        ->get(route('signatory-groups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('signatory-groups/index')
            ->has('signatoryGroups.data', 1)
            ->where('signatoryGroups.data.0.name', 'Budget Office Signatories')
            ->where('signatoryGroups.data.0.certified_signatory.full_name', 'Maria Santos')
            ->where('signatoryGroups.data.0.certified_signatory_title', 'Municipal Budget Officer')
            ->where('signatoryGroups.data.0.offices.0.office_name', 'Municipal Budget Office')
            ->has('offices')
            ->has('employees')
            ->where('filters.search', '')
        );
});

test('a group can be created and assigned offices', function () {
    $certified = Employee::factory()->create();
    $approved = Employee::factory()->create();
    $oic = Employee::factory()->create();
    $budgetOffice = Office::factory()->create();
    $mayorOffice = Office::factory()->create();

    $this->actingAs(signatoryAdmin(['signatory-groups.create']))
        ->post(route('signatory-groups.store'), [
            'name' => 'Executive Signatories',
            'certified_signatory_employee_id' => $certified->id,
            'certified_signatory_title' => 'Municipal Budget Officer',
            'approved_signatory_employee_id' => $approved->id,
            'approved_signatory_title' => 'Municipal Mayor',
            'oic_signatory_employee_id' => $oic->id,
            'oic_signatory_title' => 'Officer-in-Charge',
            'office_ids' => [$budgetOffice->id, $mayorOffice->id],
        ])
        ->assertRedirect();

    $group = SignatoryGroup::query()->where('name', 'Executive Signatories')->sole();

    expect($group->certified_signatory_employee_id)->toBe($certified->id)
        ->and($group->certified_signatory_title)->toBe('Municipal Budget Officer')
        ->and($group->approved_signatory_employee_id)->toBe($approved->id)
        ->and($group->oic_signatory_employee_id)->toBe($oic->id)
        ->and($budgetOffice->fresh()->signatory_group_id)->toBe($group->id)
        ->and($mayorOffice->fresh()->signatory_group_id)->toBe($group->id);
});

test('assigning an office moves it off its previous group', function () {
    $previous = SignatoryGroup::create(['name' => 'Previous Group']);
    $office = Office::factory()->create(['signatory_group_id' => $previous->id]);

    $this->actingAs(signatoryAdmin(['signatory-groups.create']))
        ->post(route('signatory-groups.store'), [
            'name' => 'Replacement Group',
            'office_ids' => [$office->id],
        ])
        ->assertRedirect();

    $replacement = SignatoryGroup::query()->where('name', 'Replacement Group')->sole();

    expect($office->fresh()->signatory_group_id)->toBe($replacement->id)
        ->and($previous->offices()->count())->toBe(0);
});

test('an empty office list releases the offices on update', function () {
    $group = SignatoryGroup::create(['name' => 'Old Assignment']);
    $office = Office::factory()->create(['signatory_group_id' => $group->id]);

    $this->actingAs(signatoryAdmin(['signatory-groups.update']))
        ->put(route('signatory-groups.update', $group), [
            'name' => 'Renamed Group',
            'approved_signatory_title' => 'Acting Municipal Mayor',
            'office_ids' => [],
        ])
        ->assertRedirect();

    expect($group->fresh()->name)->toBe('Renamed Group')
        ->and($group->fresh()->approved_signatory_title)->toBe('Acting Municipal Mayor')
        ->and($office->fresh()->signatory_group_id)->toBeNull();
});

test('the group name must be unique', function () {
    SignatoryGroup::create(['name' => 'Taken Name']);

    $this->actingAs(signatoryAdmin(['signatory-groups.create']))
        ->post(route('signatory-groups.store'), ['name' => 'Taken Name'])
        ->assertSessionHasErrors('name');

    expect(SignatoryGroup::query()->where('name', 'Taken Name')->count())->toBe(1);
});

test('deleting a group releases its offices', function () {
    $group = SignatoryGroup::create(['name' => 'Temporary Group']);
    $office = Office::factory()->create(['signatory_group_id' => $group->id]);

    $this->actingAs(signatoryAdmin(['signatory-groups.delete']))
        ->delete(route('signatory-groups.destroy', $group))
        ->assertRedirect();

    expect(SignatoryGroup::query()->whereKey($group->id)->exists())->toBeFalse()
        ->and($office->fresh()->signatory_group_id)->toBeNull();
});

test('an itinerary signatory group can be created and assigned offices', function () {
    $certified = Employee::factory()->create();
    $approved = Employee::factory()->create();
    $office = Office::factory()->create();

    $this->actingAs(signatoryAdmin([
        'itinerary-signatory-groups.view',
        'itinerary-signatory-groups.create',
    ]))
        ->post(route('itinerary-signatory-groups.store'), [
            'name' => 'Appendix A Signatories',
            'certified_employee_id' => $certified->id,
            'certified_title' => 'Municipal Accountant',
            'approved_employee_id' => $approved->id,
            'approved_title' => 'Municipal Mayor',
            'office_ids' => [$office->id],
        ])
        ->assertRedirect();

    $group = ItinerarySignatoryGroup::query()
        ->where('name', 'Appendix A Signatories')
        ->sole();

    expect($group->certified_employee_id)->toBe($certified->id)
        ->and($group->certified_title)->toBe('Municipal Accountant')
        ->and($group->approved_employee_id)->toBe($approved->id)
        ->and($office->fresh()->itinerary_signatory_group_id)->toBe($group->id);
});

test('the itinerary list names its two signers and offices', function () {
    $approved = Employee::factory()->create([
        'first_name' => 'Liza',
        'middle_name' => null,
        'last_name' => 'Navarro',
        'suffix' => null,
    ]);
    $office = Office::factory()->create(['office_name' => 'Office of the Mayor']);

    $group = ItinerarySignatoryGroup::create([
        'name' => 'Mayor Itinerary Signatories',
        'approved_employee_id' => $approved->id,
        'approved_title' => 'Municipal Mayor',
    ]);

    $office->update(['itinerary_signatory_group_id' => $group->id]);

    $this->actingAs(signatoryAdmin(['itinerary-signatory-groups.view']))
        ->get(route('itinerary-signatory-groups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('itinerary-signatory-groups/index')
            ->has('itinerarySignatoryGroups.data', 1)
            ->where('itinerarySignatoryGroups.data.0.name', 'Mayor Itinerary Signatories')
            ->where('itinerarySignatoryGroups.data.0.approved_employee.full_name', 'Liza Navarro')
            ->where('itinerarySignatoryGroups.data.0.approved_title', 'Municipal Mayor')
            ->where('itinerarySignatoryGroups.data.0.offices.0.office_name', 'Office of the Mayor')
            ->has('offices')
            ->has('employees')
        );
});

test('assigning an office to another itinerary group moves it', function () {
    $previous = ItinerarySignatoryGroup::create(['name' => 'Previous Itinerary Group']);
    $office = Office::factory()->create(['itinerary_signatory_group_id' => $previous->id]);

    $this->actingAs(signatoryAdmin(['itinerary-signatory-groups.create']))
        ->post(route('itinerary-signatory-groups.store'), [
            'name' => 'Replacement Itinerary Group',
            'office_ids' => [$office->id],
        ])
        ->assertRedirect();

    $replacement = ItinerarySignatoryGroup::query()
        ->where('name', 'Replacement Itinerary Group')
        ->sole();

    expect($office->fresh()->itinerary_signatory_group_id)->toBe($replacement->id)
        ->and($previous->offices()->count())->toBe(0);
});

test('an itinerary group update can clear its offices', function () {
    $group = ItinerarySignatoryGroup::create(['name' => 'Old Itinerary Assignment']);
    $office = Office::factory()->create(['itinerary_signatory_group_id' => $group->id]);

    $this->actingAs(signatoryAdmin(['itinerary-signatory-groups.update']))
        ->put(route('itinerary-signatory-groups.update', $group), [
            'name' => 'Renamed Itinerary Group',
            'office_ids' => [],
        ])
        ->assertRedirect();

    expect($group->fresh()->name)->toBe('Renamed Itinerary Group')
        ->and($office->fresh()->itinerary_signatory_group_id)->toBeNull();
});

test('an itinerary group can be deleted', function () {
    $group = ItinerarySignatoryGroup::create(['name' => 'Temporary Itinerary Group']);
    $office = Office::factory()->create(['itinerary_signatory_group_id' => $group->id]);

    $this->actingAs(signatoryAdmin(['itinerary-signatory-groups.delete']))
        ->delete(route('itinerary-signatory-groups.destroy', $group))
        ->assertRedirect();

    expect(ItinerarySignatoryGroup::query()->whereKey($group->id)->exists())->toBeFalse()
        ->and($office->fresh()->itinerary_signatory_group_id)->toBeNull();
});
