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
import travelClassificationsRoutes from '@/routes/travel-classifications';
import type { TravelClassification } from '@/types/travel-classification';
import type { FormEvent } from 'react';

type DeleteTravelClassificationDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    travelClassification: TravelClassification;
};

export function DeleteTravelClassificationDialog({
    isOpen,
    onClose,
    travelClassification,
}: DeleteTravelClassificationDialogProps) {
    const { delete: destroy, processing } = useForm();
    const isInUse = travelClassification.usage_count > 0;

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        destroy(travelClassificationsRoutes.destroy(travelClassification).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Delete Travel Classification</DialogTitle>
                        <DialogDescription>
                            {isInUse ? (
                                <>
                                    "{travelClassification.name}" is used by{' '}
                                    {travelClassification.usage_count} record(s)
                                    and cannot be deleted. Set it to inactive
                                    instead.
                                </>
                            ) : (
                                <>
                                    Are you sure you want to delete "
                                    {travelClassification.name}"? This action
                                    cannot be undone.
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
                                Delete Travel Classification
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
