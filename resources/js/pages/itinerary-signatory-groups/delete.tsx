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
import itinerarySignatoryGroupsRoutes from '@/routes/itinerary-signatory-groups';
import type { ItinerarySignatoryGroup } from '@/types/itinerary-signatory-group';
import type { FormEvent } from 'react';

type DeleteItinerarySignatoryGroupDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    itinerarySignatoryGroup: ItinerarySignatoryGroup;
};

export function DeleteItinerarySignatoryGroupDialog({
    isOpen,
    onClose,
    itinerarySignatoryGroup,
}: DeleteItinerarySignatoryGroupDialogProps) {
    const { delete: destroy, processing } = useForm();

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        destroy(
            itinerarySignatoryGroupsRoutes.destroy(itinerarySignatoryGroup).url,
            {
                onSuccess: () => onClose(),
            },
        );
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            Delete Itinerary Signatory Group
                        </DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "
                            {itinerarySignatoryGroup.name}"? This action cannot
                            be undone.
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
