<?php

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStepType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(
        Role::query()->where('slug', SystemRole::SuperAdmin->value)->sole(),
    );
});

it('stores reviewed fields on workflow steps', function () {
    $workflow = Workflow::factory()->create();
    $stepType = WorkflowStepType::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('workflows.steps.store', $workflow), [
            'workflow_step_type_id' => $stepType->id,
            'office_id' => null,
            'sort_order' => 1,
            'auto_skip_creator' => true,
            'reviewed_fields' => ['purpose', 'fund_source_id'],
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($workflow->steps()->sole()->reviewed_fields)
        ->toBe(['purpose', 'fund_source_id']);
});

it('rejects review fields that are not part of travel orders', function () {
    $workflow = Workflow::factory()->create();
    $stepType = WorkflowStepType::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('workflows.steps.store', $workflow), [
            'workflow_step_type_id' => $stepType->id,
            'office_id' => null,
            'sort_order' => 1,
            'auto_skip_creator' => true,
            'reviewed_fields' => ['unrecognized_field'],
            'is_active' => true,
        ])
        ->assertSessionHasErrors('reviewed_fields.0');

    expect($workflow->steps()->exists())->toBeFalse();
});

it('seeds workflow step types by name without creating duplicates', function () {
    $this->seed(WorkflowSeeder::class);
    $this->seed(WorkflowSeeder::class);

    expect(WorkflowStepType::query()->orderBy('sort_order')->pluck('name')->all())
        ->toBe([
            'Department Head Approval',
            'Budget Review',
            'Mayor Approval',
            'Vice Mayor Approval',
            'HRMO Review',
        ]);
});

it('creates and updates step types without a slug column', function () {
    $this->actingAs($this->admin)
        ->post(route('workflow-step-types.store'), [
            'name' => 'Budget Reviewer',
            'signature_label' => 'Reviewed',
            'signature_title' => 'Budget Officer',
            'is_signatory' => true,
            'sort_order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $stepType = WorkflowStepType::query()->where('name', 'Budget Reviewer')->sole();

    expect(Schema::hasColumn('workflow_step_types', 'slug'))->toBeFalse();
    $this->assertDatabaseHas('workflow_step_types', [
        'id' => $stepType->id,
        'name' => 'Budget Reviewer',
    ]);

    $this->actingAs($this->admin)
        ->put(route('workflow-step-types.update', $stepType), [
            'name' => 'Budget Analyst',
            'signature_label' => 'Reviewed',
            'signature_title' => 'Budget Officer',
            'is_signatory' => true,
            'sort_order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('workflow_step_types', [
        'id' => $stepType->id,
        'name' => 'Budget Analyst',
    ]);

    $this->actingAs($this->admin)
        ->get(route('workflow-step-types.index', ['search' => 'Budget Analyst']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workflow-step-types/index')
            ->has('stepTypes.data', 1)
            ->where('stepTypes.data.0.name', 'Budget Analyst')
            ->missing('stepTypes.data.0.slug')
        );
});
