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
import roles from '@/routes/roles';
import type { RoleFormData } from '@/types/access';
import type { FormEvent } from 'react';
import { PermissionPicker } from './permission-picker';

type CreateRoleDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    permissions: string[];
};

export function CreateRoleDialog({
    isOpen,
    onClose,
    permissions,
}: CreateRoleDialogProps) {
    const { data, setData, post, reset, errors, processing } =
        useForm<RoleFormData>({
            name: '',
            description: '',
            permissions: [],
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(roles.store().url, {
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
                        <DialogTitle>Create Role</DialogTitle>
                        <DialogDescription>
                            Name the role, then tick the permissions it should
                            grant. Accounts receive permissions through their
                            roles.
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
                                placeholder="e.g., Records Officer"
                                required
                                autoFocus
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
                                placeholder="What this role is for"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="space-y-2">
                            <Label>Permissions</Label>
                            <PermissionPicker
                                permissions={permissions}
                                selected={data.permissions}
                                onChange={(next) =>
                                    setData('permissions', next)
                                }
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
                            Create Role
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
