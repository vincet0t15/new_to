<?php

use App\Enums\SystemRole;
use App\Enums\TravelOrderStatus;
use App\Models\Employee;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\Role;
use App\Models\TravelOrder;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepType;
use App\Services\TravelOrderSubmissionService;

/**
 * An employee, optionally in a known office.
 */
function routingEmployee(?Office $office = null): Employee
{
    return Employee::factory()->create([
        'office_id' => ($office ?? Office::factory()->create())->id,
    ]);
}

/**
 * A login account for the employee, holding the given portal role.
 */
function routingUser(SystemRole $role, Employee $employee): User
{
    $user = User::factory()->create(['employee_id' => $employee->id]);

    $user->assignRole(Role::query()->where('slug', $role->value)->sole());

    return $user;
}

/**
 * Department Head -> Budget -> HRMO (receipt), fully designated.
 *
 * @return array{
 *     office: Office,
 *     workflow: Workflow,
 *     filer: User,
 *     filerEmployee: Employee,
 *     headUser: User,
 *     headEmployee: Employee,
 *     headType: WorkflowStepType,
 *     headStep: WorkflowStep,
 *     headDesignation: OfficeDesignation,
 *     budgetUser: User,
 *     budgetEmployee: Employee,
 *     budgetType: WorkflowStepType,
 *     budgetStep: WorkflowStep,
 *     budgetDesignation: OfficeDesignation,
 *     hrmoUser: User,
 *     hrmoEmployee: Employee,
 *     hrmoType: WorkflowStepType,
 *     hrmoStep: WorkflowStep,
 *     hrmoDesignation: OfficeDesignation
 * }
 */
function routingChain(): array
{
    $workflow = Workflow::factory()->create(['is_default' => true]);
    $office = Office::factory()->create(['workflow_id' => $workflow->id]);

    $filerEmployee = routingEmployee($office);
    $filer = routingUser(SystemRole::Employee, $filerEmployee);

    $headType = WorkflowStepType::factory()->create([
        'name' => 'Department Head',
        'sort_order' => 1,
    ]);
    $budgetType = WorkflowStepType::factory()->create([
        'name' => 'Budget',
        'sort_order' => 2,
    ]);
    $hrmoType = WorkflowStepType::factory()->receipt()->create([
        'name' => 'HRMO',
        'sort_order' => 3,
    ]);

    $headStep = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'workflow_step_type_id' => $headType->id,
        'office_id' => null,
        'sort_order' => 1,
    ]);

    $budgetOffice = Office::factory()->create();
    $budgetStep = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'workflow_step_type_id' => $budgetType->id,
        'office_id' => $budgetOffice->id,
        'sort_order' => 2,
    ]);

    $hrmoOffice = Office::factory()->create();
    $hrmoStep = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'workflow_step_type_id' => $hrmoType->id,
        'office_id' => $hrmoOffice->id,
        'sort_order' => 3,
    ]);

    $headEmployee = routingEmployee($office);
    $headUser = routingUser(SystemRole::DepartmentHead, $headEmployee);
    $headDesignation = OfficeDesignation::factory()->create([
        'office_id' => $office->id,
        'workflow_step_type_id' => $headType->id,
        'employee_id' => $headEmployee->id,
    ]);

    $budgetEmployee = routingEmployee($budgetOffice);
    $budgetUser = routingUser(SystemRole::BudgetOfficer, $budgetEmployee);
    $budgetDesignation = OfficeDesignation::factory()->create([
        'office_id' => $budgetOffice->id,
        'workflow_step_type_id' => $budgetType->id,
        'employee_id' => $budgetEmployee->id,
    ]);

    $hrmoEmployee = routingEmployee($hrmoOffice);
    $hrmoUser = routingUser(SystemRole::Hrmo, $hrmoEmployee);
    $hrmoDesignation = OfficeDesignation::factory()->create([
        'office_id' => $hrmoOffice->id,
        'workflow_step_type_id' => $hrmoType->id,
        'employee_id' => $hrmoEmployee->id,
    ]);

    return [
        'office' => $office,
        'workflow' => $workflow,
        'filer' => $filer,
        'filerEmployee' => $filerEmployee,
        'headUser' => $headUser,
        'headEmployee' => $headEmployee,
        'headType' => $headType,
        'headStep' => $headStep,
        'headDesignation' => $headDesignation,
        'budgetUser' => $budgetUser,
        'budgetEmployee' => $budgetEmployee,
        'budgetType' => $budgetType,
        'budgetStep' => $budgetStep,
        'budgetDesignation' => $budgetDesignation,
        'hrmoUser' => $hrmoUser,
        'hrmoEmployee' => $hrmoEmployee,
        'hrmoType' => $hrmoType,
        'hrmoStep' => $hrmoStep,
        'hrmoDesignation' => $hrmoDesignation,
    ];
}

/**
 * A draft filed by the fixture's creator.
 *
 * @param  array<string, mixed>  $chain
 */
function routingDraft(array $chain): TravelOrder
{
    return TravelOrder::factory()->create([
        'employee_id' => $chain['filerEmployee']->id,
        'office_id' => $chain['office']->id,
        'created_by' => $chain['filer']->id,
        'workflow_id' => null,
        'status' => TravelOrderStatus::Draft,
    ]);
}

/**
 * A draft that has been filed into the fixture's chain.
 *
 * @param  array<string, mixed>  $chain
 */
function routingSubmit(array $chain): TravelOrder
{
    return app(TravelOrderSubmissionService::class)
        ->submit(routingDraft($chain), $chain['filer']);
}
