<?php

use App\Enums\SystemRole;
use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Exceptions\TravelOrderRoutingException;
use App\Models\Employee;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\Role;
use App\Models\TravelOrder;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepType;
use App\Services\TravelOrderApprovalService;
use App\Services\TravelOrderSubmissionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

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

test('submission freezes the route and delivers the first step', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    expect($order->status)->toBe(TravelOrderStatus::Submitted)
        ->and($order->routed_steps)->toBe([
            $chain['headStep']->id,
            $chain['budgetStep']->id,
            $chain['hrmoStep']->id,
        ])
        ->and($order->current_step_index)->toBe(0)
        ->and($order->submitted_at)->not->toBeNull();

    $deliveries = $order->deliveries()->get();

    expect($deliveries)->toHaveCount(1)
        ->and($deliveries->first()->user_id)->toBe($chain['headUser']->id)
        ->and($deliveries->first()->step_index)->toBe(0)
        ->and($deliveries->first()->status)->toBe(TravelOrderDeliveryStatus::Pending);

    expect($order->actions()->where('event', WorkflowEvent::Submitted->value)->count())->toBe(1)
        ->and($order->actions()->where('event', WorkflowEvent::Routed->value)->count())->toBe(1);
});

test('a later workflow edit does not move a submitted travel order', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $extraType = WorkflowStepType::factory()->create(['name' => 'Vice Mayor']);
    WorkflowStep::factory()->create([
        'workflow_id' => $chain['workflow']->id,
        'workflow_step_type_id' => $extraType->id,
        'office_id' => $chain['office']->id,
        'sort_order' => 4,
    ]);

    $order->refresh();

    expect($order->routed_steps)->toHaveCount(3)
        ->and($order->current_step_index)->toBe(0);

    $service = app(TravelOrderApprovalService::class);

    expect($service->currentStep($order)?->id)->toBe($chain['headStep']->id);
});

test('submission is refused when a step has no active designation', function () {
    $chain = routingChain();
    $chain['budgetDesignation']->update(['is_active' => false]);
    $draft = routingDraft($chain);

    expect(fn () => app(TravelOrderSubmissionService::class)->submit($draft, $chain['filer']))
        ->toThrow(TravelOrderRoutingException::class, 'no active designation');

    $draft->refresh();

    expect($draft->status)->toBe(TravelOrderStatus::Draft)
        ->and($draft->routed_steps)->toBeNull()
        ->and($draft->deliveries()->count())->toBe(0);
});

test('submission is refused when the designee has no login account', function () {
    $chain = routingChain();
    $accountless = routingEmployee($chain['office']);
    $chain['headDesignation']->update(['employee_id' => $accountless->id]);
    $draft = routingDraft($chain);

    expect(fn () => app(TravelOrderSubmissionService::class)->submit($draft, $chain['filer']))
        ->toThrow(TravelOrderRoutingException::class, 'no login account');

    expect($draft->refresh()->status)->toBe(TravelOrderStatus::Draft);
});

test('only the creator can submit a travel order', function () {
    $chain = routingChain();
    $intruder = routingUser(SystemRole::Employee, routingEmployee($chain['office']));
    $draft = routingDraft($chain);

    expect(fn () => app(TravelOrderSubmissionService::class)->submit($draft, $intruder))
        ->toThrow(TravelOrderRoutingException::class, 'Only the person who created');
});

test('only a draft can be submitted', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    expect(fn () => app(TravelOrderSubmissionService::class)->submit($order, $chain['filer']))
        ->toThrow(TravelOrderRoutingException::class, 'Only a draft');
});

test('the filer is withheld from a step they also hold', function () {
    $chain = routingChain();
    $chain['headDesignation']->update(['employee_id' => $chain['filerEmployee']->id]);

    $order = routingSubmit($chain);

    expect($order->current_step_index)->toBe(1)
        ->and($order->deliveries()->where('user_id', $chain['headUser']->id)->count())->toBe(0);

    $withheld = $order->actions()
        ->where('event', WorkflowEvent::FilerWithheld->value)
        ->sole();

    expect($withheld->step_index)->toBe(0)
        ->and($withheld->step_label)->toBe('Department Head');
});

test('submission fails when every step would be withheld from the filer', function () {
    $chain = routingChain();

    foreach (['headDesignation', 'budgetDesignation', 'hrmoDesignation'] as $key) {
        $chain[$key]->update(['employee_id' => $chain['filerEmployee']->id]);
    }

    $draft = routingDraft($chain);

    expect(fn () => app(TravelOrderSubmissionService::class)->submit($draft, $chain['filer']))
        ->toThrow(TravelOrderRoutingException::class, 'Every step would be skipped');

    expect($draft->refresh()->status)->toBe(TravelOrderStatus::Draft);
});

test('the first holder to act claims the step and closes the deputy copy', function () {
    $chain = routingChain();

    $deputyEmployee = routingEmployee($chain['office']);
    $deputy = routingUser(SystemRole::DepartmentHead, $deputyEmployee);
    OfficeDesignation::factory()->deputy()->create([
        'office_id' => $chain['office']->id,
        'workflow_step_type_id' => $chain['headType']->id,
        'employee_id' => $deputyEmployee->id,
    ]);

    $order = routingSubmit($chain);

    expect($order->deliveries()->where('step_index', 0)->count())->toBe(2);

    $order = app(TravelOrderApprovalService::class)
        ->approve($order, $chain['headUser'], 'Cleared by the head.');

    $headDelivery = $order->deliveries()
        ->where('user_id', $chain['headUser']->id)
        ->sole();
    $deputyDelivery = $order->deliveries()
        ->where('user_id', $deputy->id)
        ->sole();

    expect($headDelivery->status)->toBe(TravelOrderDeliveryStatus::Acted)
        ->and($headDelivery->acted_via)->toBe('approve')
        ->and($deputyDelivery->status)->toBe(TravelOrderDeliveryStatus::Closed)
        ->and($order->current_step_index)->toBe(1);

    $approved = $order->actions()
        ->where('event', WorkflowEvent::Approved->value)
        ->sole();

    expect($approved->remarks)->toBe('Cleared by the head.')
        ->and($approved->actor_user_id)->toBe($chain['headUser']->id);

    expect(fn () => app(TravelOrderApprovalService::class)->approve($order, $deputy))
        ->toThrow(TravelOrderRoutingException::class, 'no longer waiting');
});

test('a receipt step must be acknowledged and a signature step approved', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);

    $order = routingSubmit($chain);

    expect(fn () => $service->acknowledge($order, $chain['headUser']))
        ->toThrow(TravelOrderRoutingException::class, 'Approve it instead');

    $order = $service->approve($order, $chain['headUser']);
    $order = $service->approve($order, $chain['budgetUser']);

    expect($order->current_step_index)->toBe(2);

    expect(fn () => $service->approve($order, $chain['hrmoUser']))
        ->toThrow(TravelOrderRoutingException::class, 'Acknowledge it instead');
});

test('the last step completes the travel order', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);

    $order = routingSubmit($chain);
    $order = $service->approve($order, $chain['headUser']);
    $order = $service->approve($order, $chain['budgetUser']);
    $order = $service->acknowledge($order, $chain['hrmoUser'], 'Received for records.');

    expect($order->status)->toBe(TravelOrderStatus::Completed)
        ->and($order->current_step_index)->toBeNull()
        ->and($order->recipient_user_id)->toBeNull()
        ->and($order->deliveries()->open()->count())->toBe(0);

    expect($order->actions()->where('event', WorkflowEvent::Acknowledged->value)->count())->toBe(1)
        ->and($order->actions()->where('event', WorkflowEvent::Completed->value)->count())->toBe(1);
});

test('returning parks the order and closes its deliveries', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $order = app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Please attach the invitation.');

    expect($order->status)->toBe(TravelOrderStatus::Returned)
        ->and($order->current_step_index)->toBeNull()
        ->and($order->deliveries()->open()->count())->toBe(0);

    $returned = $order->actions()
        ->where('event', WorkflowEvent::Returned->value)
        ->sole();

    expect($returned->remarks)->toBe('Please attach the invitation.')
        ->and($returned->step_index)->toBe(0)
        ->and($returned->actor_user_id)->toBe($chain['headUser']->id);

    expect(fn () => app(TravelOrderSubmissionService::class)->submit($order, $chain['filer']))
        ->toThrow(TravelOrderRoutingException::class, 'Only a draft');
});

test('forwarding hands the step to another designated holder', function () {
    $chain = routingChain();

    $deputyEmployee = routingEmployee($chain['office']);
    $deputy = routingUser(SystemRole::DepartmentHead, $deputyEmployee);
    OfficeDesignation::factory()->deputy()->create([
        'office_id' => $chain['office']->id,
        'workflow_step_type_id' => $chain['headType']->id,
        'employee_id' => $deputyEmployee->id,
    ]);

    $service = app(TravelOrderApprovalService::class);
    $order = routingSubmit($chain);

    expect($service->forwardCandidates($order, $chain['headUser'])->pluck('id')->all())
        ->toBe([$deputy->id]);

    $order = $service->forward($order, $chain['headUser'], $deputy, 'I am on leave.');

    $headDelivery = $order->deliveries()->where('user_id', $chain['headUser']->id)->sole();
    $deputyDelivery = $order->deliveries()->where('user_id', $deputy->id)->sole();

    expect($headDelivery->status)->toBe(TravelOrderDeliveryStatus::Acted)
        ->and($headDelivery->acted_via)->toBe('forward')
        ->and($deputyDelivery->status)->toBe(TravelOrderDeliveryStatus::Pending)
        ->and($deputyDelivery->received_at)->toBeNull()
        ->and($order->current_step_index)->toBe(0)
        ->and($order->recipient_user_id)->toBe($deputy->id);

    $forwarded = $order->actions()
        ->where('event', WorkflowEvent::Forwarded->value)
        ->sole();

    expect($forwarded->meta)->toMatchArray([
        'from_user_id' => $chain['headUser']->id,
        'to_user_id' => $deputy->id,
    ]);

    $order = $service->approve($order, $deputy);

    expect($order->current_step_index)->toBe(1);
});

test('forwarding to somebody outside the step is refused', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    expect(fn () => app(TravelOrderApprovalService::class)
        ->forward($order, $chain['headUser'], $chain['budgetUser']))
        ->toThrow(TravelOrderRoutingException::class, 'does not answer this step');
});

test('the inbox shows only the orders the account currently holds', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $this->actingAs($chain['headUser'])
        ->get(route('department-head.inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/inbox')
            ->where('portal', 'department-head')
            ->has('deliveries.data', 1)
            ->where('deliveries.data.0.id', $order->id)
            ->where('deliveries.data.0.step_label', 'Department Head')
        );

    $this->actingAs($chain['budgetUser'])
        ->get(route('approvals.inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deliveries.data', 0)
        );

    app(TravelOrderApprovalService::class)->approve($order, $chain['headUser']);

    $this->actingAs($chain['budgetUser'])
        ->get(route('approvals.inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deliveries.data', 1)
            ->where('deliveries.data.0.step_label', 'Budget')
        );
});

test('an account that never held the order cannot open it', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $stranger = routingUser(SystemRole::Mayor, routingEmployee());

    $this->actingAs($stranger)
        ->get(route('approvals.travel-orders.show', $order))
        ->assertForbidden();

    $this->actingAs($chain['headUser'])
        ->get(route('department-head.travel-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/show')
            ->where('currentStep.label', 'Department Head')
            ->where('myDeliveryStatus', 'pending')
            ->where('can.approve', true)
            ->where('can.acknowledge', false)
            ->has('forwardCandidates', 0)
        );
});

test('an approval action without a delivery is refused', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $this->actingAs($chain['budgetUser'])
        ->from(route('approvals.inbox'))
        ->post(route('approvals.travel-orders.approve', $order))
        ->assertRedirect(route('approvals.inbox'))
        ->assertSessionHasErrors('action');

    expect($order->refresh()->current_step_index)->toBe(0)
        ->and($order->deliveries()->where('user_id', $chain['budgetUser']->id)->count())->toBe(0);
});

test('the employee portal cannot reach approval actions', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $this->actingAs($chain['filer'])
        ->post(route('approvals.travel-orders.approve', $order))
        ->assertForbidden();

    expect($order->refresh()->current_step_index)->toBe(0);
});

test('submitting over HTTP files the order and redirects to it', function () {
    $chain = routingChain();
    $draft = routingDraft($chain);

    $this->actingAs($chain['filer'])
        ->post(route('travel-orders.submit', $draft))
        ->assertRedirect(route('travel-orders.show', $draft));

    $draft->refresh();

    expect($draft->status)->toBe(TravelOrderStatus::Submitted)
        ->and($draft->current_step_index)->toBe(0);
});

test('a blocked submission over HTTP reports the routing error and keeps the draft', function () {
    $chain = routingChain();
    $chain['budgetDesignation']->update(['is_active' => false]);
    $draft = routingDraft($chain);

    $response = $this->actingAs($chain['filer'])
        ->from(route('travel-orders.show', $draft))
        ->post(route('travel-orders.submit', $draft));

    $response->assertRedirect(route('travel-orders.show', $draft))
        ->assertSessionHasErrors('routing');

    expect($draft->refresh()->status)->toBe(TravelOrderStatus::Draft);
});

test('the employee show page reports route progress and abilities', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('travel-orders/show')
            ->has('route', 3)
            ->where('route.0.status', 'current')
            ->has('route.0.holders', 1)
            ->where('route.1.status', 'upcoming')
            ->where('route.2.status', 'upcoming')
            ->where('can.submit', false)
            ->where('can.edit', false)
            ->where('can.delete', false)
            ->has('timeline', 2)
        );

    $draft = routingDraft($chain);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.show', $draft))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('route.0.status', 'planned')
            ->where('can.submit', true)
            ->where('can.edit', true)
        );
});

test('the create page previews the resolved route', function () {
    $chain = routingChain();

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('travel-orders/create')
            ->has('routePreview.steps', 3)
            ->where('routePreview.is_ready', true)
            ->where('routePreview.steps.0.label', 'Department Head')
            ->where('routePreview.steps.0.designees.0.name', $chain['headEmployee']->full_name)
            ->where('routePreview.steps.2.uses_filing_office', false)
        );
});
