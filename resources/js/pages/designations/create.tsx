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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
};

export function CreateDesignationDialog({
    isOpen,
    onClose,
    offices,
    stepTypes,
    employees,
}: Props) {
    const { data, setData, post, reset, errors, processing } =
        useForm<OfficeDesignationRequest>({
            office_id: '',
            workflow_step_type_id: '',
            employee_id: '',
            is_deputy: false,
            is_active: true,
            starts_at: '',
            ends_at: '',
        });

    const filteredEmployees = employees.filter(
        (employee) =>
            data.office_id === '' ||
            employee.office_id === Number(data.office_id),
    );

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
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Add Designation</DialogTitle>
                        <DialogDescription>
                            Assign an employee to answer a step for an office.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="office_id">Office *</Label>
                            <Select
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
                            >
                                <SelectTrigger id="office_id">
                                    <SelectValue placeholder="Select an office" />
                                </SelectTrigger>
                                <SelectContent>
                                    {offices.map((office) => (
                                        <SelectItem
                                            key={office.id}
                                            value={String(office.id)}
                                        >
                                            {office.office_name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.office_id} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="workflow_step_type_id">
                                Step Type *
                            </Label>
                            <Select
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
                            >
                                <SelectTrigger id="workflow_step_type_id">
                                    <SelectValue placeholder="Select a step type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {stepTypes.map((type) => (
                                        <SelectItem
                                            key={type.id}
                                            value={String(type.id)}
                                        >
                                            {type.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={errors.workflow_step_type_id}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="employee_id">Employee *</Label>
                            <Select
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
                            >
                                <SelectTrigger id="employee_id">
                                    <SelectValue placeholder="Select an employee" />
                                </SelectTrigger>
                                <SelectContent>
                                    {filteredEmployees.map((employee) => (
                                        <SelectItem
                                            key={employee.id}
                                            value={String(employee.id)}
                                        >
                                            {employee.full_name}
                                            {!employee.has_account
                                                ? ' (no account)'
                                                : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
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
