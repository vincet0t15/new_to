import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ChevronDown,
    ChevronUp,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import workflowsRoutes from '@/routes/workflows';
import type { Office } from '@/types/office';
import type {
    Workflow,
    WorkflowStep,
    WorkflowStepType,
} from '@/types/workflow';
import type { FormEvent } from 'react';
import { StepDialog } from './step-dialog';

type Props = {
    workflow: Workflow;
    stepTypes: WorkflowStepType[];
    offices: Pick<Office, 'id' | 'office_name'>[];
    canDelete: boolean;
};

export default function WorkflowEdit({
    workflow: initial,
    stepTypes,
    offices,
    canDelete,
}: Props) {
    const [workflow, setWorkflow] = useState(initial);
    const [stepOpen, setStepOpen] = useState(false);
    const [editingStep, setEditingStep] = useState<WorkflowStep | null>(null);

    const { data, setData, put, errors, processing } = useForm({
        name: initial.name,
        slug: initial.slug,
        description: initial.description ?? '',
        is_default: initial.is_default,
        is_active: initial.is_active,
    });

    const handleSave = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        put(workflowsRoutes.update(workflow).url, {
            onSuccess: () => {
                // keep local state in sync with the saved values
                setWorkflow((prev) => ({
                    ...prev,
                    name: data.name,
                    slug: data.slug,
                    description: data.description,
                    is_default: data.is_default,
                    is_active: data.is_active,
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
                                onChange={(e) =>
                                    setData('is_default', e.target.checked)
                                }
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
                        <Button
                            type="submit"
                            disabled={processing}
                            className="ml-auto"
                        >
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
                        step={editingStep ?? undefined}
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
