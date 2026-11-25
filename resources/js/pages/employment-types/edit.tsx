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
import employmentTypes from '@/routes/employment-types';
import type {
    EmploymentType,
    EmploymentTypeUpdateRequest,
} from '@/types/employment-type';
import type { FormEvent } from 'react';

type EditEmploymentTypeDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    employmentType: EmploymentType;
};

export function EditEmploymentTypeDialog({
    isOpen,
    onClose,
    employmentType,
}: EditEmploymentTypeDialogProps) {
    const { data, setData, put, errors, processing } =
        useForm<EmploymentTypeUpdateRequest>({
            name: employmentType.name,
            description: employmentType.description ?? '',
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(employmentTypes.update(employmentType).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit Employment Type</DialogTitle>
                        <DialogDescription>
                            Update the employment type details.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name *</Label>
                            <Input
                                id="name"
                                name="name"
                                value={data.name ?? ''}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                                autoFocus
                                placeholder="Enter name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <textarea
                                id="description"
                                rows={4}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={data.description ?? ''}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                                placeholder="Optional description"
                            />
                            <p className="text-xs text-muted-foreground">
                                Optional. A short note explaining who this type
                                covers.
                            </p>
                            <InputError message={errors.description} />
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
