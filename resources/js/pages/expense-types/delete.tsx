import { useForm } from '@inertiajs/react';
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
import expenseTypes from '@/routes/expense-types';
import type { ExpenseType } from '@/types/expense-type';
import type { FormEvent } from 'react';

type DeleteExpenseTypeDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    expenseType: ExpenseType;
};

export function DeleteExpenseTypeDialog({
    isOpen,
    onClose,
    expenseType,
}: DeleteExpenseTypeDialogProps) {
    const { delete: destroy, processing } = useForm();
    const isInUse = expenseType.usage_count > 0;

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        destroy(expenseTypes.destroy(expenseType).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Delete Expense Type</DialogTitle>
                        <DialogDescription>
                            {isInUse ? (
                                <>
                                    "{expenseType.name}" is used by{' '}
                                    {expenseType.usage_count} record(s) and
                                    cannot be deleted. Set it to inactive
                                    instead.
                                </>
                            ) : (
                                <>
                                    Are you sure you want to delete "
                                    {expenseType.name}"? This action cannot be
                                    undone.
                                </>
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Close
                            </Button>
                        </DialogClose>
                        {!isInUse && (
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Delete Expense Type
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
