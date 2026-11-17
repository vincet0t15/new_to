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
import { Spinner } from '@/components/ui/spinner';
import { useCan } from '@/hooks/use-can';
import accounts from '@/routes/accounts';
import type {
    AccountRecord,
    AccountRolesFormData,
    RoleOption,
} from '@/types/access';
import type { FormEvent } from 'react';
import { RolePicker } from './role-picker';

type EditAccountRolesDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    account: AccountRecord;
    roles: RoleOption[];
};

export function EditAccountRolesDialog({
    isOpen,
    onClose,
    account,
    roles,
}: EditAccountRolesDialogProps) {
    const can = useCan();
    const { data, setData, put, errors, processing } =
        useForm<AccountRolesFormData>({
            roles: account.roles.map((role) => role.id),
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(accounts.roles.update(account).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit Roles — {account.name}</DialogTitle>
                        <DialogDescription>
                            Pick the roles this account should hold. Permissions
                            come from the roles.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        {!can('accounts.manage-admins') && (
                            <p className="text-xs text-muted-foreground">
                                Only a Super Admin can give or take away the
                                Admin or Super Admin role.
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

                    <DialogFooter className="mt-4">
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Save Roles
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
