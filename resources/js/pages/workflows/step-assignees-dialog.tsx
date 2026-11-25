import { useForm } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import designationsRoutes from '@/routes/designations';
import workflowsRoutes from '@/routes/workflows';
import type {
    OfficeDesignationRequest,
    StepDesignees,
    Workflow,
    WorkflowStep,
} from '@/types/workflow';

type Props = {
    workflow: Workflow;
    step: WorkflowStep;
    onClose: () => void;
};

export function StepAssigneesDialog({ workflow, step, onClose }: Props) {
    const [payload, setPayload] = useState<StepDesignees | null>(null);
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState<string | null>(null);

    const { data, setData, post, errors, processing, reset } =
        useForm<OfficeDesignationRequest>({
            office_id: step.office_id ?? '',
            workflow_step_type_id: step.workflow_step_type_id,
            employee_id: '',
            is_deputy: false,
            is_active: true,
            starts_at: '',
            ends_at: '',
        });

    const load = useCallback(async () => {
        setLoading(true);
        setLoadError(null);

        try {
            const response = await fetch(
                workflowsRoutes.steps.designees({ workflow, step }).url,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            if (!response.ok) {
                throw new Error('Unable to load step assignments.');
            }

            setPayload((await response.json()) as StepDesignees);
        } catch {
            setLoadError('Unable to load step assignments.');
        } finally {
            setLoading(false);
        }
    }, [workflow, step]);

    useEffect(() => {
        void load();
    }, [load]);

    const assignedEmployeeIds = new Set(
        (payload?.designations ?? [])
            .filter(
                (designation) =>
                    designation.is_active &&
                    designation.office_id === Number(data.office_id),
            )
            .map((designation) => designation.employee_id),
    );

    const employeeOptions = (payload?.employees ?? [])
        .filter((employee) => !assignedEmployeeIds.has(employee.id))
        .map((employee) => ({
            value: String(employee.id),
            label: `${employee.full_name} · ${employee.office_name}${employee.has_account ? '' : ' (no account)'}`,
        }));

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(designationsRoutes.store().url, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('employee_id', 'is_deputy');
                void load();
            },
        });
    };

    return (
        <Dialog open onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Step Assignees</DialogTitle>
                    <DialogDescription>
                        Employees who answer this step. Add a holder here
                        without leaving the workflow; other steps are not
                        changed.
                    </DialogDescription>
                </DialogHeader>

                {loading && payload === null ? (
                    <div
                        role="status"
                        className="flex items-center gap-2 py-8 text-sm text-muted-foreground"
                    >
                        <Spinner />
                        Loading step assignees...
                    </div>
                ) : loadError ? (
                    <div role="alert" className="space-y-3 py-4">
                        <p className="text-sm text-destructive">{loadError}</p>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => void load()}
                        >
                            Retry
                        </Button>
                    </div>
                ) : payload ? (
                    <>
                        <div className="flex flex-wrap gap-x-6 gap-y-1 text-sm">
                            <span>
                                <span className="text-muted-foreground">
                                    Step:{' '}
                                </span>
                                {payload.step.label}
                            </span>
                            <span>
                                <span className="text-muted-foreground">
                                    Step type:{' '}
                                </span>
                                {payload.step.step_type.name}
                            </span>
                            <span>
                                <span className="text-muted-foreground">
                                    Office:{' '}
                                </span>
                                {payload.step.office?.office_name ??
                                    'Filing office'}
                            </span>
                        </div>

                        <div className="w-full overflow-hidden rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Employee</TableHead>
                                        <TableHead>Office</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Account</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payload.designations.length > 0 ? (
                                        payload.designations.map(
                                            (designation) => (
                                                <TableRow key={designation.id}>
                                                    <TableCell className="font-medium">
                                                        {
                                                            designation.employee
                                                                .full_name
                                                        }
                                                    </TableCell>
                                                    <TableCell>
                                                        {
                                                            designation.office
                                                                .office_name
                                                        }
                                                    </TableCell>
                                                    <TableCell>
                                                        {designation.is_deputy
                                                            ? 'Deputy'
                                                            : 'Primary'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {designation.is_active
                                                            ? 'Active'
                                                            : 'Ended'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {designation.employee
                                                            .user
                                                            ? 'Linked'
                                                            : 'No account'}
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="py-6 text-center text-sm text-muted-foreground"
                                            >
                                                No employees are assigned to
                                                this step yet.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <form
                            onSubmit={handleSubmit}
                            className="space-y-3 rounded-md border bg-muted/20 p-4"
                        >
                            <h3 className="text-sm font-semibold">
                                Add employee to this step
                            </h3>
                            {payload.step.office === null && (
                                <div className="space-y-2">
                                    <Label htmlFor="assignee_office_id">
                                        Office *
                                    </Label>
                                    <Combobox
                                        id="assignee_office_id"
                                        value={
                                            data.office_id === ''
                                                ? ''
                                                : String(data.office_id)
                                        }
                                        onValueChange={(value) =>
                                            setData(
                                                'office_id',
                                                value === ''
                                                    ? ''
                                                    : Number(value),
                                            )
                                        }
                                        options={payload.offices.map(
                                            (office) => ({
                                                value: String(office.id),
                                                label: office.office_name,
                                            }),
                                        )}
                                        placeholder="Select an office"
                                        searchPlaceholder="Search office..."
                                        emptyMessage="No offices found."
                                        className="w-full"
                                    />
                                    <InputError message={errors.office_id} />
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="assignee_employee_id">
                                    Employee *
                                </Label>
                                <Combobox
                                    id="assignee_employee_id"
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
                                    options={employeeOptions}
                                    placeholder="Select an employee"
                                    searchPlaceholder="Search employee or office..."
                                    emptyMessage="No unassigned employees found."
                                    className="w-full"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Employees already holding this step for the
                                    selected office are not listed.
                                </p>
                                <InputError message={errors.employee_id} />
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="assignee_is_deputy"
                                    checked={data.is_deputy}
                                    onCheckedChange={(checked) =>
                                        setData('is_deputy', checked === true)
                                    }
                                />
                                <Label htmlFor="assignee_is_deputy">
                                    Deputy
                                </Label>
                            </div>
                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Add Employee
                                </Button>
                            </div>
                        </form>
                    </>
                ) : null}
            </DialogContent>
        </Dialog>
    );
}
