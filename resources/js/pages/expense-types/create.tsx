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
import type { ExpenseTypeCreateRequest } from '@/types/expense-type';
import type { FormEvent } from 'react';

type CreateExpenseTypeDialogProps = {
    isOpen: boolean;
    onClose: () => void;
};

export function CreateExpenseTypeDialog({
    isOpen,
    onClose,
}: CreateExpenseTypeDialogProps) {
    const { data, setData, post, reset, errors, processing } =
        useForm<ExpenseTypeCreateRequest>({
            name: '',
            description: '',
            is_default: true,
            is_transportation: false,
            is_active: true,
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(expenseTypes.store().url, {
            onSuccess: () => {
                onClose();
                reset();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Create Expense Type</DialogTitle>
                        <DialogDescription>
                            Add an expense category that can be claimed on a
                            travel order.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name *</Label>
                            <Input
                                id="name"
                                name="name"
                                value={data.name}
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
                            <Textarea
                                id="description"
                                name="description"
                                rows={3}
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                                placeholder="Optional description"
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
                    </div>

                    <DialogFooter className="mt-4">
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Create Expense Type
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
