import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ChevronDown,
    ChevronUp,
    Pencil,
    Plus,
    Trash2,
    Users,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import workflowsRoutes from '@/routes/workflows';
import type { WorkflowOfficeOption } from '@/types/office';
import type {
    Workflow,
    WorkflowStep,
    WorkflowStepType,
    WorkflowUpdateRequest,
} from '@/types/workflow';
import type { FormEvent } from 'react';
import { StepAssigneesDialog } from './step-assignees-dialog';
import { StepDialog } from './step-dialog';

type Props = {
    workflow: Workflow;
    stepTypes: WorkflowStepType[];
    offices: WorkflowOfficeOption[];
    assignedOfficeIds: number[];
    reviewableFields: Record<string, string>;
    canDelete: boolean;
};

export default function WorkflowEdit({
    workflow: initial,
    stepTypes,
    offices,
    assignedOfficeIds,
    reviewableFields,
    canDelete,
}: Props) {
    const [workflow, setWorkflow] = useState(initial);
    const [stepOpen, setStepOpen] = useState(false);
    const [editingStep, setEditingStep] = useState<WorkflowStep | null>(null);
    const [assigningStep, setAssigningStep] = useState<WorkflowStep | null>(
        null,
    );

    const { data, setData, put, errors, processing } =
        useForm<WorkflowUpdateRequest>({
            name: initial.name,
            slug: initial.slug,
            description: initial.description ?? '',
            is_default: initial.is_default,
            is_active: initial.is_active,
            assigned_office_ids: assignedOfficeIds,
        });
    const assignedOfficeError =
        errors.assigned_office_ids ??
        Object.entries(errors).find(([key]) =>
            key.startsWith('assigned_office_ids.'),
        )?.[1];

    useEffect(() => {
        setWorkflow(initial);
    }, [initial]);

    const selectedOfficeIds = new Set(data.assigned_office_ids);

    const handleSave = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const officesToReassign = data.is_default
            ? []
            : offices.filter(
                  (office) =>
                      selectedOfficeIds.has(office.id) &&
                      office.workflow_id !== null &&
                      office.workflow_id !== workflow.id,
              );

        if (
            officesToReassign.length > 0 &&
            !window.confirm(
                `These offices will be moved from their current workflow to "${data.name}": ${officesToReassign
                    .map((office) => office.office_name)
                    .join(', ')}. Continue?`,
            )
        ) {
            return;
        }

        put(workflowsRoutes.update(workflow).url, {
            onSuccess: () => {
                const isActive = data.is_default || data.is_active;

                if (isActive !== data.is_active) {
                    setData('is_active', isActive);
                }

                // keep local state in sync with the saved values
                setWorkflow((prev) => ({
                    ...prev,
                    name: data.name,
                    slug: data.slug,
                    description: data.description,
                    is_default: data.is_default,
                    is_active: isActive,
                }));
            },
        });
    };

    const handleMove = (step: WorkflowStep, direction: 'up' | 'down') => {
        router.post(
            workflowsRoutes.steps.move({ workflow, step }).url,
            { direction },
            { preserveScroll: true },
        );
    };

    const handleDeleteStep = (step: WorkflowStep) => {
        if (!confirm(`Remove step "${step.label ?? step.step_type.name}"?`))
            return;
        router.delete(workflowsRoutes.steps.destroy({ workflow, step }).url, {
            preserveScroll: true,
        });
    };

    const openAdd = () => {
        setEditingStep(null);
        setStepOpen(true);
    };

    const openEdit = (step: WorkflowStep) => {
        setEditingStep(step);
        setStepOpen(true);
    };

    return (
        <>
            <Head title={`Workflow: ${workflow.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            router.visit(workflowsRoutes.index().url)
                        }
                    >
                        <ArrowLeft className="size-4" />
                        Back
                    </Button>
                    <Heading
                        title={workflow.name}
                        description="Edit the workflow's details and ordered steps."
                    />
                </div>

                <form
                    onSubmit={handleSave}
                    className="space-y-4 rounded-xl border p-4"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="slug">Slug *</Label>
                            <Input
                                id="slug"
                                value={data.slug}
                                onChange={(e) =>
                                    setData('slug', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.slug} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            rows={2}
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError message={errors.description} />
                    </div>
                    <div className="flex flex-wrap items-center gap-4">
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={data.is_default}
                                onChange={(e) => {
                                    setData('is_default', e.target.checked);
                                    if (e.target.checked) {
                                        setData('assigned_office_ids', []);
                                    }
                                }}
                            />
                            <span className="text-sm">Default workflow</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) =>
                                    setData('is_active', e.target.checked)
                                }
                            />
                            <span className="text-sm">Active</span>
                        </label>
                    </div>

                    {data.is_default ? (
                        <div className="rounded-md border bg-muted/30 p-3 text-sm text-muted-foreground">
                            This is the fallback workflow for offices without a
                            specific assignment. Explicit assignments to it are
                            cleared when you save.
                        </div>
                    ) : (
                        <section className="space-y-3 rounded-md border p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h2 className="text-sm font-semibold">
                                        Assigned Offices
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Select the offices that use this
                                        workflow instead of the default. No
                                        selection means offices use the default
                                        workflow.
                                    </p>
                                </div>
                                <span className="text-xs text-muted-foreground tabular-nums">
                                    {data.assigned_office_ids.length} selected
                                </span>
                            </div>

                            <div className="max-h-64 space-y-1 overflow-y-auto rounded-md border p-2">
                                {offices.length > 0 ? (
                                    offices.map((office) => {
                                        const isSelected =
                                            selectedOfficeIds.has(office.id);
                                        const assignedElsewhere =
                                            office.workflow_id !== null &&
                                            office.workflow_id !== workflow.id;

                                        return (
                                            <label
                                                key={office.id}
                                                className="flex cursor-pointer items-start gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-muted/50"
                                            >
                                                <Checkbox
                                                    className="mt-0.5"
                                                    checked={isSelected}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) => {
                                                        setData(
                                                            'assigned_office_ids',
                                                            checked === true
                                                                ? [
                                                                      ...data.assigned_office_ids,
                                                                      office.id,
                                                                  ]
                                                                : data.assigned_office_ids.filter(
                                                                      (id) =>
                                                                          id !==
                                                                          office.id,
                                                                  ),
                                                        );
                                                    }}
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <span className="block truncate">
                                                        {office.office_name}
                                                    </span>
                                                    {assignedElsewhere && (
                                                        <span className="block text-xs text-muted-foreground">
                                                            Currently assigned
                                                            to{' '}
                                                            {office.workflow
                                                                ?.name ??
                                                                'another workflow'}
                                                            ; selecting it will
                                                            move the assignment.
                                                        </span>
                                                    )}
                                                </span>
                                            </label>
                                        );
                                    })
                                ) : (
                                    <p className="px-2 py-4 text-center text-sm text-muted-foreground">
                                        No offices are available.
                                    </p>
                                )}
                            </div>
                            <InputError message={assignedOfficeError} />
                        </section>
                    )}

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Save Changes
                        </Button>
                    </div>
                </form>

                <div className="flex items-center justify-between">
                    <Heading
                        title="Steps"
                        description="Ordered steps in this chain."
                    />
                    <Can permission="workflow.manage">
                        <Button onClick={openAdd}>
                            <Plus />
                            Add Step
                        </Button>
                    </Can>
                </div>

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-3 py-2 text-left">#</th>
                                <th className="px-3 py-2 text-left">Step</th>
                                <th className="px-3 py-2 text-left">Type</th>
                                <th className="px-3 py-2 text-left">
                                    Target Office
                                </th>
                                <th className="px-3 py-2 text-left">Active</th>
                                <th className="px-3 py-2 text-right">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {workflow.steps.length > 0 ? (
                                workflow.steps.map((step, index) => (
                                    <tr key={step.id} className="border-t">
                                        <td className="px-3 py-2">
                                            {index + 1}
                                        </td>
                                        <td className="px-3 py-2 font-medium">
                                            {step.label || step.step_type.name}
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground">
                                            {step.step_type.name}
                                        </td>
                                        <td className="px-3 py-2">
                                            {step.office
                                                ? step.office.office_name
                                                : 'Filing office'}
                                        </td>
                                        <td className="px-3 py-2">
                                            {step.is_active ? 'Yes' : 'No'}
                                        </td>
                                        <td className="px-3 py-2">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    onClick={() =>
                                                        setAssigningStep(step)
                                                    }
                                                    title="Assign employees for this step"
                                                    aria-label="Assign employees for this step"
                                                >
                                                    <Users />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    onClick={() =>
                                                        handleMove(step, 'up')
                                                    }
                                                    disabled={index === 0}
                                                    title="Move up"
                                                >
                                                    <ChevronUp />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    onClick={() =>
                                                        handleMove(step, 'down')
                                                    }
                                                    disabled={
                                                        index ===
                                                        workflow.steps.length -
                                                            1
                                                    }
                                                    title="Move down"
                                                >
                                                    <ChevronDown />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-blue-600"
                                                    onClick={() =>
                                                        openEdit(step)
                                                    }
                                                    title="Edit step"
                                                >
                                                    <Pencil />
                                                </Button>
                                                {canDelete && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-red-600"
                                                        onClick={() =>
                                                            handleDeleteStep(
                                                                step,
                                                            )
                                                        }
                                                        title="Delete step"
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No steps yet. Add the first step to this
                                        chain.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {stepOpen && (
                    <StepDialog
                        isOpen={stepOpen}
                        onClose={() => setStepOpen(false)}
                        workflowId={workflow.id}
                        stepTypes={stepTypes}
                        offices={offices}
                        reviewableFields={reviewableFields}
                        step={editingStep ?? undefined}
                    />
                )}

                {assigningStep && (
                    <StepAssigneesDialog
                        workflow={workflow}
                        step={assigningStep}
                        onClose={() => setAssigningStep(null)}
                    />
                )}
            </div>
        </>
    );
}

WorkflowEdit.layout = {
    breadcrumbs: [
        { title: 'Workflows', href: workflowsRoutes.index().url },
        { title: 'Edit', href: workflowsRoutes.edit({ workflow: 0 }).url },
    ],
};
