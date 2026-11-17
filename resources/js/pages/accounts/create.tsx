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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useCan } from '@/hooks/use-can';
import accounts from '@/routes/accounts';
import type {
    AccountFormData,
    EmployeeAccountOption,
    RoleOption,
} from '@/types/access';
import type { FormEvent } from 'react';
import { RolePicker } from './role-picker';

type CreateAccountDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    roles: RoleOption[];
    employees: EmployeeAccountOption[];
};

export function CreateAccountDialog({
    isOpen,
    onClose,
    roles,
    employees,
}: CreateAccountDialogProps) {
    const can = useCan();
    const employeeRole = roles.find((role) => role.slug === 'employee');

    const { data, setData, post, reset, errors, processing } =
        useForm<AccountFormData>({
            name: '',
            username: '',
            email: '',
            password: '',
            password_confirmation: '',
            employee_id: '',
            roles: employeeRole ? [employeeRole.id] : [],
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(accounts.store().url, {
            onSuccess: () => {
                onClose();
                reset();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Create Account</DialogTitle>
                        <DialogDescription>
                            Create a login, link it to an employee, and give it
                            the roles it needs.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="name">Full Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="username">Username *</Label>
                                <Input
                                    id="username"
                                    value={data.username}
                                    onChange={(event) =>
                                        setData('username', event.target.value)
                                    }
                                    placeholder="e.g., jdelacruz"
                                    required
                                />
                                <InputError message={errors.username} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                placeholder="Optional contact email"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="password">Password *</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(event) =>
                                        setData('password', event.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm Password *
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(event) =>
                                        setData(
                                            'password_confirmation',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="employee_id">Linked Employee</Label>
                            <Combobox
                                id="employee_id"
                                value={
                                    data.employee_id === ''
                                        ? 'none'
                                        : data.employee_id
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'employee_id',
                                        value === 'none' ? '' : value,
                                    )
                                }
                                options={[
                                    {
                                        value: 'none',
                                        label: 'No employee record',
                                    },
                                    ...employees.map((employee) => ({
                                        value: String(employee.id),
                                        label: `${employee.full_name} — ${employee.office_name}`,
                                    })),
                                ]}
                                placeholder="No employee record"
                                searchPlaceholder="Search employees..."
                                emptyMessage="No employees without a login."
                            />
                            <p className="text-xs text-muted-foreground">
                                Only active employees without a login are
                                listed.
                            </p>
                            <InputError message={errors.employee_id} />
                        </div>

                        <div className="space-y-2">
                            <Label>Roles *</Label>
                            {!can('accounts.manage-admins') && (
                                <p className="text-xs text-muted-foreground">
                                    Only a Super Admin can give the Admin or
                                    Super Admin role.
                                </p>
                            )}
                            <RolePicker
                                roles={roles}
                                selected={data.roles}
                                onChange={(next) => setData('roles', next)}
                                canManageAdmins={can('accounts.manage-admins')}
                            />
                            <InputError message={errors.roles} />
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
                            Create Account
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
