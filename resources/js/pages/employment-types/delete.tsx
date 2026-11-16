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
import employmentTypes from '@/routes/employment-types';
import type { EmploymentType } from '@/types/employment-type';
import type { FormEvent } from 'react';

type DeleteEmploymentTypeDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    employmentType: EmploymentType;
};

export function DeleteEmploymentTypeDialog({
    isOpen,
    onClose,
    employmentType,
}: DeleteEmploymentTypeDialogProps) {
    const { delete: destroy, processing } = useForm();

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        destroy(employmentTypes.destroy(employmentType).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Delete Employment Type</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "
                            {employmentType.name}"? This action cannot be
                            undone.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Delete Employment Type
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
