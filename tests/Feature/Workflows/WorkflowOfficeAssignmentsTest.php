<?php

use App\Enums\SystemRole;
use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(
        Role::query()->where('slug', SystemRole::SuperAdmin->value)->sole(),
    );
});

it('shows available offices and existing assignments in the workflow editor', function () {
    $workflow = Workflow::factory()->create();
    $office = Office::factory()->create(['workflow_id' => $workflow->id]);

    $this->actingAs($this->admin)
        ->get(route('workflows.edit', $workflow))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workflows/edit')
            ->where('assignedOfficeIds', [$office->id])
            ->where('offices.0.id', $office->id)
            ->where('offices.0.workflow_id', $workflow->id)
        );
});

it('assigns selected offices and releases removed offices to the default fallback', function () {
    $workflow = Workflow::factory()->create();
    $previousWorkflow = Workflow::factory()->create();
    $removedOffice = Office::factory()->create(['workflow_id' => $workflow->id]);
    $movedOffice = Office::factory()->create(['workflow_id' => $previousWorkflow->id]);
    $unassignedOffice = Office::factory()->create(['workflow_id' => null]);

    $this->actingAs($this->admin)
        ->put(route('workflows.update', $workflow), [
            'name' => $workflow->name,
            'slug' => $workflow->slug,
            'description' => $workflow->description,
            'is_default' => false,
            'is_active' => true,
            'assigned_office_ids' => [$movedOffice->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('offices', [
        'id' => $removedOffice->id,
        'workflow_id' => null,
    ]);
    $this->assertDatabaseHas('offices', [
        'id' => $movedOffice->id,
        'workflow_id' => $workflow->id,
    ]);
    $this->assertDatabaseHas('offices', [
        'id' => $unassignedOffice->id,
        'workflow_id' => null,
    ]);
});

it('clears explicit office assignments when a workflow becomes the default', function () {
    $previousDefault = Workflow::factory()->default()->create();
    $workflow = Workflow::factory()->create();
    $previousDefaultOffice = Office::factory()->create([
        'workflow_id' => $previousDefault->id,
    ]);
    $workflowOffice = Office::factory()->create(['workflow_id' => $workflow->id]);

    $this->actingAs($this->admin)
        ->put(route('workflows.update', $workflow), [
            'name' => $workflow->name,
            'slug' => $workflow->slug,
            'description' => $workflow->description,
            'is_default' => true,
            'is_active' => true,
            'assigned_office_ids' => [],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($workflow->fresh()->is_default)->toBeTrue()
        ->and($previousDefault->fresh()->is_default)->toBeFalse();
    $this->assertDatabaseHas('offices', [
        'id' => $previousDefaultOffice->id,
        'workflow_id' => null,
    ]);
    $this->assertDatabaseHas('offices', [
        'id' => $workflowOffice->id,
        'workflow_id' => null,
    ]);
});

it('releases offices from the previous default when creating a new default', function () {
    $previousDefault = Workflow::factory()->default()->create();
    $office = Office::factory()->create([
        'workflow_id' => $previousDefault->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('workflows.store'), [
            'name' => 'New Default Workflow',
            'description' => 'The new fallback workflow.',
            'is_default' => true,
            'is_active' => true,
        ])
        ->assertRedirect();

    $newDefault = Workflow::query()
        ->where('name', 'New Default Workflow')
        ->sole();

    expect($newDefault->is_default)->toBeTrue()
        ->and($previousDefault->fresh()->is_default)->toBeFalse();
    $this->assertDatabaseHas('offices', [
        'id' => $office->id,
        'workflow_id' => null,
    ]);
});

it('rejects office assignments that reference a missing office', function () {
    $workflow = Workflow::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('workflows.update', $workflow), [
            'name' => $workflow->name,
            'slug' => $workflow->slug,
            'description' => $workflow->description,
            'is_default' => false,
            'is_active' => true,
            'assigned_office_ids' => [999999],
        ])
        ->assertSessionHasErrors('assigned_office_ids.0');

    expect($workflow->fresh()->name)->toBe($workflow->name);
});
