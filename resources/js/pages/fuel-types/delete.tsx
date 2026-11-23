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
import fuelTypes from '@/routes/fuel-types';
import type { FuelType } from '@/types/fuel-type';
import type { FormEvent } from 'react';

type Props = {
    isOpen: boolean;
    onClose: () => void;
    fuelType: FuelType;
};

export function DeleteFuelTypeDialog({ isOpen, onClose, fuelType }: Props) {
    const { delete: destroy, processing } = useForm();
    const isInUse = fuelType.usage_count > 0;

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        destroy(fuelTypes.destroy(fuelType).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Delete Fuel Type</DialogTitle>
                        <DialogDescription>
                            {isInUse
                                ? `"${fuelType.name}" is used by ${fuelType.usage_count} record(s) and cannot be deleted. Set it to inactive instead.`
                                : `Are you sure you want to delete "${fuelType.name}"? This action cannot be undone.`}
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
                                {processing && <Spinner />}Delete Fuel Type
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
