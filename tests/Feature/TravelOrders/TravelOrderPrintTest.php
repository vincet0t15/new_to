<?php

use App\Enums\SystemRole;
use App\Models\Employee;
use App\Models\ItinerarySignatoryGroup;
use App\Models\Role;
use App\Models\SignatoryGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * An account with the given system role and no other capabilities.
 */
function printRoleUser(SystemRole $role, ?Employee $employee = null): User
{
    $user = User::factory()->create([
        'employee_id' => ($employee ?? routingEmployee())->id,
    ]);

    $user->assignRole(Role::query()->where('slug', $role->value)->sole());

    return $user;
}

/**
 * A named employee, pinned so full_name is predictable in assertions.
 */
function printEmployee(string $firstName, string $lastName): Employee
{
    return Employee::factory()->create([
        'first_name' => $firstName,
        'middle_name' => null,
        'last_name' => $lastName,
        'suffix' => null,
    ]);
}

test('the owner can print the travel order form', function () {
    $chain = routingChain();
    $order = routingDraft($chain);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.print.order', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('travel-orders/print-order')
            ->where('travelOrder.id', $order->id)
            ->where('letterhead.republic', config('travel-orders.letterhead.republic'))
            ->where('letterhead.province', config('travel-orders.letterhead.province'))
            ->where('letterhead.municipality', config('travel-orders.letterhead.municipality'))
            ->has('signatories.department_head_approval')
            ->has('signatories.budget_office_approval')
            ->has('signatories.mayor_approval')
            ->has('signatories.oic_approval')
        );
});

test('the owner can print the itinerary of travel', function () {
    $chain = routingChain();
    $order = routingDraft($chain);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.print.itinerary', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('travel-orders/print-itinerary')
            ->where('travelOrder.id', $order->id)
            ->where('letterhead.republic', config('travel-orders.letterhead.republic'))
            ->has('travelOrder.itineraries')
            // An office without a group prints the standard role titles over
            // blank names.
            ->where('itinerarySignatories.certified.name', null)
            ->where('itinerarySignatories.certified.title', 'Municipal Budget Officer')
            ->where('itinerarySignatories.approved.name', null)
            ->where('itinerarySignatories.approved.title', 'Municipal Mayor')
        );
});

test('an approver the order was delivered to can print it', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $this->actingAs($chain['headUser'])
        ->get(route('travel-orders.print.order', $order))
        ->assertOk();

    $this->actingAs($chain['headUser'])
        ->get(route('travel-orders.print.itinerary', $order))
        ->assertOk();
});

test('an unrelated account cannot print the order', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);
    $stranger = printRoleUser(SystemRole::Mayor);

    $this->actingAs($stranger)
        ->get(route('travel-orders.print.order', $order))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->get(route('travel-orders.print.itinerary', $order))
        ->assertForbidden();
});

test('printing requires the print permission even for the owner', function () {
    $chain = routingChain();
    $stranger = User::factory()->create();
    $order = routingDraft($chain);
    $order->forceFill(['created_by' => $stranger->id])->save();

    $this->actingAs($stranger)
        ->get(route('travel-orders.print.order', $order))
        ->assertForbidden();
});

test('a filed order keeps the signatories frozen when it was submitted', function () {
    $chain = routingChain();

    $signer = printEmployee('Liza', 'Navarro');
    $group = SignatoryGroup::create([
        'name' => 'Mayor Signatories',
        'approved_signatory_employee_id' => $signer->id,
        'approved_signatory_title' => 'Acting Municipal Mayor',
    ]);

    $chain['office']->update(['signatory_group_id' => $group->id]);

    $order = routingSubmit($chain);

    expect($order->fresh()->signatories['mayor_approval']['name'])
        ->toBe('Liza Navarro');

    // The seat changes hands after filing; the paper copy must not.
    $successor = printEmployee('Ramon', 'Cruz');
    $group->update([
        'approved_signatory_employee_id' => $successor->id,
        'approved_signatory_title' => 'Municipal Mayor',
    ]);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.print.order', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('signatories.mayor_approval.name', 'Liza Navarro')
            ->where('signatories.mayor_approval.title_override', 'Acting Municipal Mayor')
        );
});

test('a draft prints the signatories as the designations stand now', function () {
    $chain = routingChain();

    $signer = printEmployee('Ramon', 'Cruz');
    $group = SignatoryGroup::create([
        'name' => 'Current Mayor Signatories',
        'approved_signatory_employee_id' => $signer->id,
        'approved_signatory_title' => 'Municipal Mayor',
    ]);

    $chain['office']->update(['signatory_group_id' => $group->id]);

    $order = routingDraft($chain);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.print.order', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('signatories.mayor_approval.name', 'Ramon Cruz')
            ->where('signatories.mayor_approval.title_override', 'Municipal Mayor')
        );
});

test('the itinerary prints the office group signers', function () {
    $chain = routingChain();

    $certified = printEmployee('Maria', 'Santos');
    $approved = printEmployee('Ramon', 'Cruz');
    $group = ItinerarySignatoryGroup::create([
        'name' => 'Office Itinerary Signatories',
        'certified_employee_id' => $certified->id,
        'certified_title' => 'Municipal Accountant',
        'approved_employee_id' => $approved->id,
        'approved_title' => 'Municipal Mayor',
    ]);

    $chain['office']->update(['itinerary_signatory_group_id' => $group->id]);

    $order = routingDraft($chain);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.print.itinerary', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('itinerarySignatories.certified.name', 'Maria Santos')
            ->where('itinerarySignatories.certified.title', 'Municipal Accountant')
            ->where('itinerarySignatories.approved.name', 'Ramon Cruz')
            ->where('itinerarySignatories.approved.title', 'Municipal Mayor')
        );
});

test('a group title prints even when it leaves the signer to the designation', function () {
    $chain = routingChain();

    $group = ItinerarySignatoryGroup::create([
        'name' => 'Title Only Signatories',
        'certified_title' => 'Acting Municipal Budget Officer',
    ]);

    $chain['office']->update(['itinerary_signatory_group_id' => $group->id]);

    $order = routingDraft($chain);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.print.itinerary', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('itinerarySignatories.certified.name', null)
            ->where('itinerarySignatories.certified.title', 'Acting Municipal Budget Officer')
            ->where('itinerarySignatories.approved.title', 'Municipal Mayor')
        );
});

test('the show page offers both print links', function () {
    $chain = routingChain();
    $order = routingDraft($chain);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('urls.printOrder', route('travel-orders.print.order', $order))
            ->where('urls.printItinerary', route('travel-orders.print.itinerary', $order))
        );
});
