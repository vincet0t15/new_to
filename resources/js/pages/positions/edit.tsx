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
import positions from '@/routes/positions';
import type { EmploymentType } from '@/types/employment-type';
import type { Position, PositionUpdateRequest } from '@/types/position';
import type { FormEvent } from 'react';

type EditPositionDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    position: Position;
    employmentTypes: Pick<EmploymentType, 'id' | 'name'>[];
};

export function EditPositionDialog({
    isOpen,
    onClose,
    position,
    employmentTypes,
}: EditPositionDialogProps) {
    const { data, setData, put, errors, processing } =
        useForm<PositionUpdateRequest>({
            name: position.name,
            description: position.description ?? '',
            employment_type_id: position.employment_type_id ?? '',
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(positions.update(position).url, {
            onSuccess: () => onClose(),
        });
    };

    const employmentTypeValue =
        data.employment_type_id === '' || data.employment_type_id === undefined
            ? 'none'
            : String(data.employment_type_id);

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit Position</DialogTitle>
                        <DialogDescription>
                            Update the position details.
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
                            <Label htmlFor="employment_type_id">
                                Employment Type
                            </Label>
                            <Combobox
                                id="employment_type_id"
                                value={employmentTypeValue}
                                onValueChange={(value) =>
                                    setData(
                                        'employment_type_id',
                                        value === 'none' ? '' : Number(value),
                                    )
                                }
                                options={[
                                    {
                                        value: 'none',
                                        label: 'No employment type',
                                    },
                                    ...employmentTypes.map(
                                        (employmentType) => ({
                                            value: String(employmentType.id),
                                            label: employmentType.name,
                                        }),
                                    ),
                                ]}
                                placeholder="Select an employment type"
                                searchPlaceholder="Search employment type..."
                                emptyMessage="No employment types found."
                                className="w-full"
                            />
                            <p className="text-xs text-muted-foreground">
                                Optional. Leave blank if the position is not
                                tied to one.
                            </p>
                            <InputError message={errors.employment_type_id} />
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
