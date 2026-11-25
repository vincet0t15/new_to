<?php

use App\Models\Employee;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\Permission;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepType;

/**
 * An account that may edit workflows and read designations, without seeding
 * the full role catalogue that would grant everything to the test account.
 *
 * @param  array<int, string>  $permissions
 */
function stepDesigneeUser(array $permissions = ['workflow.manage', 'designations.manage']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    grantAdminPortal($user);
    $user->givePermissionTo($permissions);

    return $user;
}

test('the step dialog lists only the designations for the clicked step', function () {
    $workflow = Workflow::factory()->create();
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $stepType = WorkflowStepType::factory()->create();
    $otherStepType = WorkflowStepType::factory()->create();
    $step = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'workflow_step_type_id' => $stepType->id,
        'office_id' => $office->id,
    ]);

    $holder = Employee::factory()->create(['office_id' => $office->id]);
    $deputy = Employee::factory()->create(['office_id' => $office->id]);
    $wrongType = Employee::factory()->create(['office_id' => $office->id]);
    $wrongOffice = Employee::factory()->create(['office_id' => $otherOffice->id]);

    OfficeDesignation::factory()->create([
        'office_id' => $office->id,
        'workflow_step_type_id' => $stepType->id,
        'employee_id' => $holder->id,
        'is_deputy' => false,
    ]);
    OfficeDesignation::factory()->create([
        'office_id' => $office->id,
        'workflow_step_type_id' => $stepType->id,
        'employee_id' => $deputy->id,
        'is_deputy' => true,
    ]);
    OfficeDesignation::factory()->create([
        'office_id' => $office->id,
        'workflow_step_type_id' => $otherStepType->id,
        'employee_id' => $wrongType->id,
    ]);
    OfficeDesignation::factory()->create([
        'office_id' => $otherOffice->id,
        'workflow_step_type_id' => $stepType->id,
        'employee_id' => $wrongOffice->id,
    ]);

    $response = $this->actingAs(stepDesigneeUser())
        ->getJson(route('workflows.steps.designees', [$workflow, $step]))
        ->assertOk()
        ->assertJsonCount(2, 'designations')
        ->assertJsonCount(1, 'offices')
        ->assertJsonPath('step.step_type.id', $stepType->id)
        ->assertJsonPath('step.office.id', $office->id);

    expect($response->json('designations.*.employee_id'))
        ->toContain($holder->id, $deputy->id)
        ->not->toContain($wrongType->id, $wrongOffice->id);
    expect($response->json('offices.0.id'))->toBe($office->id);
    expect($response->json('employees.0'))->toHaveKey('has_account');
});

test('a filing-office step shows the step type across offices', function () {
    $workflow = Workflow::factory()->create();
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $stepType = WorkflowStepType::factory()->create();
    $step = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'workflow_step_type_id' => $stepType->id,
        'office_id' => null,
    ]);

    foreach ([$office, $otherOffice] as $coveredOffice) {
        $employee = Employee::factory()->create(['office_id' => $coveredOffice->id]);

        OfficeDesignation::factory()->create([
            'office_id' => $coveredOffice->id,
            'workflow_step_type_id' => $stepType->id,
            'employee_id' => $employee->id,
        ]);
    }

    $this->actingAs(stepDesigneeUser())
        ->getJson(route('workflows.steps.designees', [$workflow, $step]))
        ->assertOk()
        ->assertJsonCount(2, 'designations')
        ->assertJsonCount(2, 'offices')
        ->assertJsonPath('step.office', null);
});

test('a step from another workflow cannot be read through a workflow url', function () {
    $workflow = Workflow::factory()->create();
    $otherWorkflow = Workflow::factory()->create();
    $step = WorkflowStep::factory()->create(['workflow_id' => $otherWorkflow->id]);

    $this->actingAs(stepDesigneeUser())
        ->getJson(route('workflows.steps.designees', [$workflow, $step]))
        ->assertNotFound();
});

test('reading step designees requires designation management permission', function () {
    $workflow = Workflow::factory()->create();
    $step = WorkflowStep::factory()->create(['workflow_id' => $workflow->id]);

    $this->actingAs(stepDesigneeUser(['workflow.manage']))
        ->getJson(route('workflows.steps.designees', [$workflow, $step]))
        ->assertForbidden();
});
