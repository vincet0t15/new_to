import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Textarea } from '@/components/ui/textarea';
import expenseTypes from '@/routes/expense-types';
import type {
    ExpenseType,
    ExpenseTypeCreateRequest,
} from '@/types/expense-type';
import type { FormEvent } from 'react';

type EditExpenseTypeDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    expenseType: ExpenseType;
};

export function EditExpenseTypeDialog({
    isOpen,
    onClose,
    expenseType,
}: EditExpenseTypeDialogProps) {
    const { data, setData, put, errors, processing } =
        useForm<ExpenseTypeCreateRequest>({
            name: expenseType.name,
            description: expenseType.description ?? '',
            is_default: expenseType.is_default,
            is_transportation: expenseType.is_transportation,
            is_active: expenseType.is_active,
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(expenseTypes.update(expenseType).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit Expense Type</DialogTitle>
                        <DialogDescription>
                            Update the expense type details.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name *</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="e.g., Hotel Accommodation"
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
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                name="description"
                                placeholder="e.g., Lodging for official travel"
                                rows={3}
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="space-y-3 rounded-md border p-3">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_default"
                                    checked={data.is_default}
                                    onCheckedChange={(checked) =>
                                        setData('is_default', checked === true)
                                    }
                                />
                                <Label htmlFor="is_default">
                                    Default (pre-selected on new orders)
                                </Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_transportation"
                                    checked={data.is_transportation}
                                    onCheckedChange={(checked) =>
                                        setData(
                                            'is_transportation',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor="is_transportation">
                                    Transportation
                                </Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label htmlFor="is_active">
                                    Active (available for selection)
                                </Label>
                            </div>
                        </div>

                        <p className="text-xs text-muted-foreground">
                            Default types are pre-selected on new orders.
                            Transportation types are used for fares. Inactive
                            types stay hidden from new entries.
                        </p>

                        {expenseType.usage_count > 0 && (
                            <p className="text-sm text-muted-foreground">
                                This expense type is used by{' '}
                                {expenseType.usage_count} record(s), so it
                                cannot be deleted. Set it to inactive to hide it
                                from new entries.
                            </p>
                        )}
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
