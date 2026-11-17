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
import locations from '@/routes/locations';
import type { Location } from '@/types/location';
import type { FormEvent } from 'react';

type DeleteLocationDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    location: Location;
};

export function DeleteLocationDialog({
    isOpen,
    onClose,
    location,
}: DeleteLocationDialogProps) {
    const { delete: destroy, processing } = useForm();
    const isInUse = location.usage_count > 0;

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        destroy(locations.destroy(location).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Delete Location</DialogTitle>
                        <DialogDescription>
                            {isInUse ? (
                                <>
                                    "{location.name}" is used by{' '}
                                    {location.usage_count} record(s) and cannot
                                    be deleted. Set it to inactive instead.
                                </>
                            ) : (
                                <>
                                    Are you sure you want to delete "
                                    {location.name}"? This action cannot be
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
                                Delete Location
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
