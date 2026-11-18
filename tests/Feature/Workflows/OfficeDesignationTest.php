<?php

use App\Enums\SystemRole;
use App\Models\Employee;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowStepType;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * An account that may manage designations.
 */
function designationAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(
        Role::query()->where('slug', SystemRole::Admin->value)->sole(),
    );

    return $user;
}

test('an employee from another office can hold an acting designation', function () {
    $coveredOffice = Office::factory()->create();
    $homeOffice = Office::factory()->create();
    $employee = Employee::factory()->create(['office_id' => $homeOffice->id]);
    $stepType = WorkflowStepType::factory()->create();

    $this->actingAs(designationAdmin())
        ->post(route('designations.store'), [
            'office_id' => $coveredOffice->id,
            'workflow_step_type_id' => $stepType->id,
            'employee_id' => $employee->id,
            'is_deputy' => false,
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('office_designations', [
        'office_id' => $coveredOffice->id,
        'workflow_step_type_id' => $stepType->id,
        'employee_id' => $employee->id,
        'is_active' => true,
    ]);
});

test('the same employee cannot hold two active designations for one step', function () {
    $office = Office::factory()->create();
    $employee = Employee::factory()->create(['office_id' => $office->id]);
    $stepType = WorkflowStepType::factory()->create();

    OfficeDesignation::factory()->create([
        'office_id' => $office->id,
        'workflow_step_type_id' => $stepType->id,
        'employee_id' => $employee->id,
    ]);

    $this->actingAs(designationAdmin())
        ->from(route('designations.index'))
        ->post(route('designations.store'), [
            'office_id' => $office->id,
            'workflow_step_type_id' => $stepType->id,
            'employee_id' => $employee->id,
            'is_active' => true,
        ])
        ->assertRedirect(route('designations.index'))
        ->assertSessionHasErrors('employee_id');
});

test('a travel order reaches an acting holder from another office', function () {
    $chain = routingChain();

    $actingEmployee = routingEmployee();
    $actingUser = routingUser(SystemRole::DepartmentHead, $actingEmployee);

    $chain['headDesignation']->update(['employee_id' => $actingEmployee->id]);

    $order = routingSubmit($chain);

    expect($order->deliveries()
        ->where('step_index', 0)
        ->where('user_id', $actingUser->id)
        ->exists())->toBeTrue();
});
