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
import vehicles from '@/routes/vehicles';
import type { Vehicle } from '@/types';
import type { FormEvent } from 'react';

type Props = {
    isOpen: boolean;
    onClose: () => void;
    vehicle: Vehicle;
};

export function DeleteVehicleDialog({ isOpen, onClose, vehicle }: Props) {
    const { delete: destroy, processing } = useForm();
    const isInUse = vehicle.usage_count > 0;

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        destroy(vehicles.destroy(vehicle).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Delete vehicle</DialogTitle>
                        <DialogDescription>
                            {isInUse
                                ? `"${vehicle.plate_no}" is used by ${vehicle.usage_count} trip ticket(s) and cannot be deleted. Set it to inactive instead.`
                                : `Are you sure you want to delete "${vehicle.plate_no}"? This action cannot be undone.`}
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
                                {processing && <Spinner />}Delete vehicle
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
