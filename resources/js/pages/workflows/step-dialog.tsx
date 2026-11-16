import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Combobox } from '@/components/ui/combobox';
import { Spinner } from '@/components/ui/spinner';
import workflows from '@/routes/workflows';
import type { Office } from '@/types/office';
import type {
    WorkflowStep,
    WorkflowStepRequest,
    WorkflowStepType,
} from '@/types/workflow';
import type { FormEvent } from 'react';

type Props = {
    isOpen: boolean;
    onClose: () => void;
    workflowId: number;
    stepTypes: WorkflowStepType[];
    offices: Pick<Office, 'id' | 'office_name'>[];
    step?: WorkflowStep;
};

export function StepDialog({
    isOpen,
    onClose,
    workflowId,
    stepTypes,
    offices,
    step,
}: Props) {
    const isEdit = Boolean(step);
    const { data, setData, post, put, reset, errors, processing } =
        useForm<WorkflowStepRequest>({
            workflow_step_type_id: step?.workflow_step_type_id ?? '',
            label: step?.label ?? '',
            office_id: step?.office_id ?? '',
            sort_order: step?.sort_order ?? 1,
            auto_skip_creator: step?.auto_skip_creator ?? true,
            is_active: step?.is_active ?? true,
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (isEdit && step) {
            put(
                workflows.steps.update({ workflow: workflowId, step: step.id })
                    .url,
                {
                    onSuccess: () => {
                        onClose();
                        reset();
                    },
                },
            );
        } else {
            post(workflows.steps.store({ workflow: workflowId }).url, {
                onSuccess: () => {
                    onClose();
                    reset();
                },
            });
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEdit ? 'Edit Step' : 'Add Step'}
                        </DialogTitle>
                        <DialogDescription>
                            Configure a step in this approval chain.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="workflow_step_type_id">
                                Step Type *
                            </Label>
                            <Combobox
                                id="workflow_step_type_id"
                                value={
                                    data.workflow_step_type_id === ''
                                        ? ''
                                        : String(data.workflow_step_type_id)
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'workflow_step_type_id',
                                        value === '' ? '' : Number(value),
                                    )
                                }
                                options={stepTypes.map((type) => ({
                                    value: String(type.id),
                                    label: type.name,
                                }))}
                                placeholder="Select a step type"
                                searchPlaceholder="Search step type..."
                                emptyMessage="No step types found."
                            />
                            <InputError
                                message={errors.workflow_step_type_id}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="label">Label (optional)</Label>
                            <Input
                                id="label"
                                value={data.label}
                                onChange={(e) =>
                                    setData('label', e.target.value)
                                }
                                placeholder="Override the step type name"
                            />
                            <InputError message={errors.label} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="office_id">Target Office</Label>
                            <Combobox
                                id="office_id"
                                value={
                                    data.office_id === ''
                                        ? 'filing'
                                        : String(data.office_id)
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'office_id',
                                        value === 'filing' ? '' : Number(value),
                                    )
                                }
                                options={[
                                    {
                                        value: 'filing',
                                        label: 'Filing office (follows the filer)',
                                    },
                                    ...offices.map((office) => ({
                                        value: String(office.id),
                                        label: office.office_name,
                                    })),
                                ]}
                                placeholder="Filing office"
                                searchPlaceholder="Search office..."
                                emptyMessage="No offices found."
                            />
                            <p className="text-xs text-muted-foreground">
                                Only offices that answer this step can be chosen
                                here; the full office list is available on the
                                designations page.
                            </p>
                            <InputError message={errors.office_id} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sort_order">Sort Order *</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                min={1}
                                value={data.sort_order}
                                onChange={(e) =>
                                    setData(
                                        'sort_order',
                                        Number(e.target.value),
                                    )
                                }
                                required
                            />
                            <InputError message={errors.sort_order} />
                        </div>
                        <div className="flex items-center gap-2">
                            <input
                                id="auto_skip_creator"
                                type="checkbox"
                                checked={data.auto_skip_creator}
                                onChange={(e) =>
                                    setData(
                                        'auto_skip_creator',
                                        e.target.checked,
                                    )
                                }
                            />
                            <Label htmlFor="auto_skip_creator">
                                Skip when the creator holds this step
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <input
                                id="is_active"
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) =>
                                    setData('is_active', e.target.checked)
                                }
                            />
                            <Label htmlFor="is_active">Active</Label>
                        </div>
                    </div>
                    <DialogFooter className="mt-4">
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {isEdit ? 'Save Changes' : 'Add Step'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
