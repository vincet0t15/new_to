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
import signatoryGroupsRoutes from '@/routes/signatory-groups';
import type { SignatoryGroup } from '@/types/signatory-group';
import type { FormEvent } from 'react';

type DeleteSignatoryGroupDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    signatoryGroup: SignatoryGroup;
};

export function DeleteSignatoryGroupDialog({
    isOpen,
    onClose,
    signatoryGroup,
}: DeleteSignatoryGroupDialogProps) {
    const { delete: destroy, processing } = useForm();

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        destroy(signatoryGroupsRoutes.destroy(signatoryGroup).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            Delete Travel Order Signatory Group
                        </DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "
                            {signatoryGroup.name}"? This action cannot be
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
                            Delete Group
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
