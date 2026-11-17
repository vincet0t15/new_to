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
import { Spinner } from '@/components/ui/spinner';
import { useCan } from '@/hooks/use-can';
import roles from '@/routes/roles';
import type { RoleFormData, RoleRecord } from '@/types/access';
import type { FormEvent } from 'react';
import { PermissionPicker } from './permission-picker';

type EditRoleDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    role: RoleRecord;
    permissions: string[];
};

export function EditRoleDialog({
    isOpen,
    onClose,
    role,
    permissions,
}: EditRoleDialogProps) {
    const can = useCan();
    const { data, setData, put, errors, processing } = useForm<RoleFormData>({
        name: role.name,
        description: role.description ?? '',
        permissions: role.permissions,
    });

    const isSuperAdmin = role.slug === 'super_admin';
    const permissionsLocked =
        isSuperAdmin || (role.is_system && !can('accounts.manage-admins'));

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(roles.update(role).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit Role — {role.name}</DialogTitle>
                        <DialogDescription>
                            {role.is_system
                                ? 'Built-in role. Its name and slug are fixed so the system keeps working.'
                                : 'Update the role name and the permissions it grants.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Role Name *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                                disabled={role.is_system}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="space-y-2">
                            <Label>Permissions</Label>
                            {isSuperAdmin && (
                                <p className="text-xs text-muted-foreground">
                                    The Super Admin role always holds every
                                    permission and cannot be reduced.
                                </p>
                            )}
                            {role.is_system &&
                                !isSuperAdmin &&
                                !can('accounts.manage-admins') && (
                                    <p className="text-xs text-muted-foreground">
                                        Only a Super Admin can change the
                                        permissions of a built-in role.
                                    </p>
                                )}
                            <PermissionPicker
                                permissions={permissions}
                                selected={data.permissions}
                                onChange={(next) =>
                                    setData('permissions', next)
                                }
                                disabled={permissionsLocked}
                            />
                            <InputError message={errors.permissions} />
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
                            Save Changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
