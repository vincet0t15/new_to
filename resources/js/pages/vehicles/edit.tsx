import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
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
import vehicles from '@/routes/vehicles';
import type { TripTicketDriver, Vehicle, VehicleCreateRequest } from '@/types';
import type { FormEvent } from 'react';

type Props = {
    isOpen: boolean;
    onClose: () => void;
    vehicle: Vehicle;
    drivers: TripTicketDriver[];
};

export function EditVehicleDialog({
    isOpen,
    onClose,
    vehicle,
    drivers,
}: Props) {
    const { data, setData, put, errors, processing } =
        useForm<VehicleCreateRequest>({
            plate_no: vehicle.plate_no,
            vehicle_type: vehicle.vehicle_type ?? '',
            model: vehicle.model ?? '',
            capacity: vehicle.capacity !== null ? String(vehicle.capacity) : '',
            driver_id:
                vehicle.driver_id !== null ? String(vehicle.driver_id) : '',
            is_active: vehicle.is_active,
        });

    const driverChoices = drivers.map((driver) => ({
        value: String(driver.id),
        label: driver.full_name,
    }));

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        put(vehicles.update(vehicle).url, { onSuccess: () => onClose() });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit vehicle</DialogTitle>
                        <DialogDescription>
                            Update the registration details and assigned driver.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="plate_no">Plate number *</Label>
                            <Input
                                id="plate_no"
                                value={data.plate_no}
                                onChange={(event) =>
                                    setData('plate_no', event.target.value)
                                }
                                required
                                placeholder="e.g., ABC-1234"
                            />
                            <InputError message={errors.plate_no} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="vehicle_type">Vehicle type</Label>
                            <Input
                                id="vehicle_type"
                                value={data.vehicle_type}
                                onChange={(event) =>
                                    setData('vehicle_type', event.target.value)
                                }
                                placeholder="e.g., Van"
                            />
                            <InputError message={errors.vehicle_type} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="model">Model</Label>
                            <Input
                                id="model"
                                value={data.model}
                                onChange={(event) =>
                                    setData('model', event.target.value)
                                }
                                placeholder="e.g., Toyota HiAce"
                            />
                            <InputError message={errors.model} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="capacity">Seating capacity</Label>
                            <Input
                                id="capacity"
                                type="number"
                                min="1"
                                max="999"
                                value={data.capacity}
                                onChange={(event) =>
                                    setData('capacity', event.target.value)
                                }
                                placeholder="e.g., 15"
                            />
                            <InputError message={errors.capacity} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="driver_id">Assigned driver</Label>
                            <Combobox
                                id="driver_id"
                                value={data.driver_id}
                                onValueChange={(value) =>
                                    setData('driver_id', value)
                                }
                                options={driverChoices}
                                placeholder="Select the driver"
                                searchPlaceholder="Search employee..."
                            />
                            <InputError message={errors.driver_id} />
                        </div>
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) =>
                                    setData('is_active', checked === true)
                                }
                            />
                            <Label htmlFor="is_active">Active</Label>
                        </div>
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}Update vehicle
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
