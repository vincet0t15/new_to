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
import designations from '@/routes/designations';
import type {
    DesignationEmployeeOption,
    OfficeDesignationRequest,
} from '@/types/workflow';
import type { Office } from '@/types/office';
import type { WorkflowStepType } from '@/types/workflow';
import type { FormEvent } from 'react';

type Props = {
    isOpen: boolean;
    onClose: () => void;
    offices: Pick<Office, 'id' | 'office_name'>[];
    stepTypes: WorkflowStepType[];
    employees: DesignationEmployeeOption[];
    initialOfficeId: number | null;
    initialStepTypeId: number | null;
};

export function CreateDesignationDialog({
    isOpen,
    onClose,
    offices,
    stepTypes,
    employees,
    initialOfficeId,
    initialStepTypeId,
}: Props) {
    const { data, setData, post, reset, errors, processing } =
        useForm<OfficeDesignationRequest>({
            office_id: initialOfficeId ?? '',
            workflow_step_type_id: initialStepTypeId ?? '',
            employee_id: '',
            is_deputy: false,
            is_active: true,
            starts_at: '',
            ends_at: '',
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(designations.store().url, {
            onSuccess: () => {
                onClose();
                reset();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Add Designation</DialogTitle>
                        <DialogDescription>
                            Assign an employee to answer a step for an office.
                            The employee may come from another office when
                            acting on the seat.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="office_id">Office *</Label>
                            <Combobox
                                id="office_id"
                                value={
                                    data.office_id === ''
                                        ? ''
                                        : String(data.office_id)
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'office_id',
                                        value === '' ? '' : Number(value),
                                    )
                                }
                                options={offices.map((office) => ({
                                    value: String(office.id),
                                    label: office.office_name,
                                }))}
                                placeholder="Select an office"
                                searchPlaceholder="Search office..."
                                emptyMessage="No offices found."
                            />
                            <InputError message={errors.office_id} />
                        </div>
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
                            <Label htmlFor="employee_id">Employee *</Label>
                            <Combobox
                                id="employee_id"
                                value={
                                    data.employee_id === ''
                                        ? ''
                                        : String(data.employee_id)
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'employee_id',
                                        value === '' ? '' : Number(value),
                                    )
                                }
                                options={employees.map((employee) => ({
                                    value: String(employee.id),
                                    label: `${employee.full_name} · ${employee.office_name}${employee.has_account ? '' : ' (no account)'}`,
                                }))}
                                placeholder="Select an employee"
                                searchPlaceholder="Search employee or office..."
                                emptyMessage="No employees found."
                            />
                            <p className="text-xs text-muted-foreground">
                                Any active employee can hold this step, even one
                                from another office.
                            </p>
                            <InputError message={errors.employee_id} />
                        </div>
                        <div className="flex items-center gap-2">
                            <input
                                id="is_deputy"
                                type="checkbox"
                                checked={data.is_deputy}
                                onChange={(e) =>
                                    setData('is_deputy', e.target.checked)
                                }
                            />
                            <Label htmlFor="is_deputy">Deputy</Label>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="starts_at">Starts At</Label>
                                <Input
                                    id="starts_at"
                                    type="date"
                                    value={data.starts_at}
                                    onChange={(e) =>
                                        setData('starts_at', e.target.value)
                                    }
                                />
                                <InputError message={errors.starts_at} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="ends_at">Ends At</Label>
                                <Input
                                    id="ends_at"
                                    type="date"
                                    value={data.ends_at}
                                    onChange={(e) =>
                                        setData('ends_at', e.target.value)
                                    }
                                />
                                <InputError message={errors.ends_at} />
                            </div>
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
                            Add Designation
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
