import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import accounts from '@/routes/accounts';
import type {
    AccountEmployeeFormData,
    AccountRecord,
    EmployeeAccountOption,
} from '@/types/access';
import type { FormEvent } from 'react';

type LinkEmployeeDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    account: AccountRecord;
    employees: EmployeeAccountOption[];
};

export function LinkEmployeeDialog({
    isOpen,
    onClose,
    account,
    employees,
}: LinkEmployeeDialogProps) {
    const linkedEmployee = account.employee;
    const initialValue =
        linkedEmployee === null ? 'none' : String(linkedEmployee.id);

    const { data, setData, transform, put, errors, processing } =
        useForm<AccountEmployeeFormData>({
            employee_id: initialValue,
        });

    const options = [
        { value: 'none', label: 'No employee record' },
        ...employees.map((employee) => ({
            value: String(employee.id),
            label: `${employee.full_name} — ${employee.office_name}`,
        })),
    ];

    if (
        linkedEmployee !== null &&
        !options.some((option) => option.value === String(linkedEmployee.id))
    ) {
        options.push({
            value: String(linkedEmployee.id),
            label: `${linkedEmployee.full_name} — ${linkedEmployee.office_name ?? 'No office'}`,
        });
    }

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        transform(() => ({
            employee_id:
                data.employee_id === 'none' ? null : Number(data.employee_id),
        }));

        put(accounts.employee.update(account).url, {
            preserveState: false,
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            Employee Link — {account.name}
                        </DialogTitle>
                        <DialogDescription>
                            Link this account to an employee record. Choose “No
                            employee record” to remove the current link.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2 py-2">
                        <Label htmlFor="employee_id">Employee</Label>
                        <Combobox
                            id="employee_id"
                            value={data.employee_id}
                            onValueChange={(value) =>
                                setData('employee_id', value)
                            }
                            options={options}
                            placeholder="No employee record"
                            searchPlaceholder="Search employees..."
                            emptyMessage="No employees available."
                        />
                        <p className="text-xs text-muted-foreground">
                            Only active employees without another account are
                            listed. The current employee is kept so it can be
                            left unchanged.
                        </p>
                        <InputError message={errors.employee_id} />
                    </div>

                    <DialogFooter className="mt-4">
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Save Employee Link
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
