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
import positions from '@/routes/positions';
import type { EmploymentType } from '@/types/employment-type';
import type { PositionCreateRequest } from '@/types/position';
import type { FormEvent } from 'react';

type CreatePositionDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    employmentTypes: Pick<EmploymentType, 'id' | 'name'>[];
};

export function CreatePositionDialog({
    isOpen,
    onClose,
    employmentTypes,
}: CreatePositionDialogProps) {
    const { data, setData, post, reset, errors, processing } =
        useForm<PositionCreateRequest>({
            name: '',
            description: '',
            employment_type_id: '',
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(positions.store().url, {
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
                        <DialogTitle>Create Position</DialogTitle>
                        <DialogDescription>
                            Add a job title that employees can hold.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name *</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="e.g., Administrative Officer"
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
                            <Label htmlFor="employment_type_id">
                                Employment Type
                            </Label>
                            <Select
                                value={
                                    data.employment_type_id === ''
                                        ? 'none'
                                        : String(data.employment_type_id)
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'employment_type_id',
                                        value === 'none' ? '' : Number(value),
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="employment_type_id"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select an employment type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        No employment type
                                    </SelectItem>
                                    {employmentTypes.map((employmentType) => (
                                        <SelectItem
                                            key={employmentType.id}
                                            value={String(employmentType.id)}
                                        >
                                            {employmentType.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                Optional. Leave blank if the position is not
                                tied to one.
                            </p>
                            <InputError message={errors.employment_type_id} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Input
                                id="description"
                                name="description"
                                placeholder="e.g., Handles administrative records"
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
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
                            Create Position
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
