import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Fuel, Save } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/date-format';
import type {
    TripTicket,
    TripTicketDriver,
    TripTicketTravelOrder,
} from '@/types';

type Props = {
    travelOrder: TripTicketTravelOrder;
    drivers: TripTicketDriver[];
    offices: { id: number; office_name: string }[];
    fuelTypes: { id: number; name: string }[];
    tripTicket?: TripTicket;
    action: { url: string; method: 'post' | 'put' };
    cancelUrl: string;
};

type TripTicketFormData = {
    vehicle_plate_no: string;
    vehicle_type: string;
    driver_id: string;
    fuel_type_id: string;
    fuel_type: string;
    fuel_source_office_id: string;
    fuel_efficiency: string;
    distance_km: string;
    round_trip: boolean;
    odometer_start: string;
    fuel_price_per_liter: string;
    fuel_cost: string;
    fuel_station: string;
    fuel_receipt_no: string;
    fuel_receipt: File | null;
    remove_fuel_receipt: boolean;
};

function formatDateRange(from: string | null, to: string | null): string {
    if (!from || !to) {
        return '—';
    }

    return `${formatDate(from)} – ${formatDate(to)}`;
}

export function TripTicketForm({
    travelOrder,
    drivers,
    offices,
    fuelTypes,
    tripTicket,
    action,
    cancelUrl,
}: Props) {
    const defaultFuelOfficeId = tripTicket?.fuel_source_office_id
        ? String(tripTicket.fuel_source_office_id)
        : travelOrder.office?.id
          ? String(travelOrder.office.id)
          : '';

    const form = useForm<TripTicketFormData>({
        vehicle_plate_no: tripTicket?.vehicle_plate_no ?? '',
        vehicle_type: tripTicket?.vehicle_type ?? '',
        driver_id:
            tripTicket?.driver_id !== null &&
            tripTicket?.driver_id !== undefined
                ? String(tripTicket.driver_id)
                : '',
        fuel_type_id: tripTicket?.fuel_type_id ? String(tripTicket.fuel_type_id) : '',
        fuel_type: tripTicket?.fuel_type ?? '',
        fuel_source_office_id: defaultFuelOfficeId,
        fuel_efficiency: tripTicket?.fuel_efficiency ?? '',
        distance_km: tripTicket?.distance_km ?? '',
        round_trip: tripTicket?.round_trip ?? true,
        odometer_start: tripTicket?.odometer_start ?? '',
        fuel_price_per_liter: tripTicket?.fuel_price_per_liter ?? '',
        fuel_cost: tripTicket?.fuel_cost ?? '',
        fuel_station: tripTicket?.fuel_station ?? '',
        fuel_receipt_no: tripTicket?.fuel_receipt_no ?? '',
        fuel_receipt: null,
        remove_fuel_receipt: false,
    });

    const driverChoices = drivers.map((driver) => ({
        value: String(driver.id),
        label: driver.full_name,
    }));

    const efficiency = Number(form.data.fuel_efficiency || 0);
    const distance = Number(form.data.distance_km || 0);
    const estimated =
        efficiency > 0 && distance > 0
            ? (distance * (form.data.round_trip ? 2 : 1)) / efficiency
            : 0;
    const fuelPrice = Number(form.data.fuel_price_per_liter || 0);
    const estimatedCost =
        form.data.fuel_cost !== ''
            ? Number(form.data.fuel_cost || 0)
            : fuelPrice > 0 && estimated > 0
              ? fuelPrice * estimated
              : 0;

    const submit = () => {
        form.transform((data) => ({
            ...data,
            _method: action.method,
        }));
        form.post(action.url, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <div className="grid gap-6 lg:grid-cols-3">
            <div className="space-y-4 lg:col-span-2">
                <section className="space-y-4 rounded-xl border p-4">
                    <div>
                        <h3 className="text-base font-semibold">
                            Travel order
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            {travelOrder.control_no ??
                                travelOrder.to_number ??
                                `#${travelOrder.id}`}{' '}
                            ·{' '}
                            {formatDateRange(
                                travelOrder.date_from,
                                travelOrder.date_to,
                            )}
                        </p>
                        <p className="mt-1 text-sm">{travelOrder.purpose}</p>
                    </div>
                </section>

                <section className="space-y-4 rounded-xl border p-4">
                    <h3 className="text-base font-semibold">Vehicle</h3>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="vehicle_plate_no">
                                Plate number *
                            </Label>
                            <Input
                                id="vehicle_plate_no"
                                value={form.data.vehicle_plate_no}
                                onChange={(event) =>
                                    form.setData(
                                        'vehicle_plate_no',
                                        event.target.value,
                                    )
                                }
                                placeholder="e.g., ABC-1234"
                            />
                            <InputError
                                message={form.errors.vehicle_plate_no}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="vehicle_type">Vehicle type</Label>
                            <Input
                                id="vehicle_type"
                                value={form.data.vehicle_type}
                                onChange={(event) =>
                                    form.setData(
                                        'vehicle_type',
                                        event.target.value,
                                    )
                                }
                                placeholder="e.g., Van, pickup, motorcycle"
                            />
                            <InputError message={form.errors.vehicle_type} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="driver_id">Driver</Label>
                            <Combobox
                                id="driver_id"
                                value={form.data.driver_id}
                                onValueChange={(value) =>
                                    form.setData('driver_id', value)
                                }
                                options={driverChoices}
                                placeholder="Select the driver"
                                searchPlaceholder="Search employee..."
                            />
                            <InputError message={form.errors.driver_id} />
                        </div>
                        <div className="space-y-2">
                            <Label>Fuel type</Label>
                            <Combobox
                                value={form.data.fuel_type_id}
                                onValueChange={(value) => {
                                    const selected = fuelTypes.find((ft) => String(ft.id) === value);
                                    form.setData((data) => ({
                                        ...data,
                                        fuel_type_id: value,
                                        fuel_type: selected?.name ?? data.fuel_type,
                                    }));
                                }}
                                options={fuelTypes.map((ft) => ({ value: String(ft.id), label: ft.name }))}
                                placeholder="Select fuel type"
                                searchPlaceholder="Search fuel type..."
                            />
                            <InputError message={form.errors.fuel_type_id ?? form.errors.fuel_type} />
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label>Fuel source office</Label>
                            <Combobox
                                value={form.data.fuel_source_office_id}
                                onValueChange={(value) =>
                                    form.setData('fuel_source_office_id', value)
                                }
                                options={offices.map((office) => ({
                                    value: String(office.id),
                                    label: office.office_name,
                                }))}
                                placeholder="Select fuel source office"
                                searchPlaceholder="Search office..."
                            />
                            <p className="text-muted-foreground text-xs">
                                Default is the travel order&apos;s office. Change if fuel will be borrowed/charged to another office.
                            </p>
                            <InputError message={form.errors.fuel_source_office_id} />
                        </div>
                    </div>
                </section>

                <section className="space-y-4 rounded-xl border p-4">
                    <h3 className="text-base font-semibold">Trip figures</h3>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="distance_km">
                                Distance one way (km) *
                            </Label>
                            <Input
                                id="distance_km"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.distance_km}
                                onChange={(event) =>
                                    form.setData(
                                        'distance_km',
                                        event.target.value,
                                    )
                                }
                                placeholder="e.g., 120.00"
                            />
                            <InputError message={form.errors.distance_km} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="odometer_start">
                                Odometer reading at departure
                            </Label>
                            <Input
                                id="odometer_start"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.odometer_start}
                                onChange={(event) =>
                                    form.setData(
                                        'odometer_start',
                                        event.target.value,
                                    )
                                }
                                placeholder="e.g., 45210.50"
                            />
                            <InputError message={form.errors.odometer_start} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="fuel_efficiency">
                                Fuel efficiency (km/L)
                            </Label>
                            <Input
                                id="fuel_efficiency"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.fuel_efficiency}
                                onChange={(event) =>
                                    form.setData(
                                        'fuel_efficiency',
                                        event.target.value,
                                    )
                                }
                                placeholder="e.g., 10.00"
                            />
                            <InputError message={form.errors.fuel_efficiency} />
                        </div>
                        <div className="flex items-center gap-2 pt-6">
                            <Checkbox
                                id="round_trip"
                                checked={form.data.round_trip}
                                onCheckedChange={(checked) =>
                                    form.setData('round_trip', checked === true)
                                }
                            />
                            <Label htmlFor="round_trip">
                                Round trip (distance is travelled twice)
                            </Label>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="fuel_price_per_liter">
                                Fuel price per liter
                            </Label>
                            <Input
                                id="fuel_price_per_liter"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.fuel_price_per_liter}
                                onChange={(event) =>
                                    form.setData(
                                        'fuel_price_per_liter',
                                        event.target.value,
                                    )
                                }
                                placeholder="e.g., 65.00"
                            />
                            <InputError
                                message={form.errors.fuel_price_per_liter}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="fuel_cost">Fuel cost</Label>
                            <Input
                                id="fuel_cost"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.fuel_cost}
                                onChange={(event) =>
                                    form.setData('fuel_cost', event.target.value)
                                }
                                placeholder={
                                    estimatedCost > 0
                                        ? estimatedCost.toFixed(2)
                                        : 'e.g., 1560.00'
                                }
                            />
                            <InputError message={form.errors.fuel_cost} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="fuel_station">Fuel station</Label>
                            <Input
                                id="fuel_station"
                                value={form.data.fuel_station}
                                onChange={(event) =>
                                    form.setData(
                                        'fuel_station',
                                        event.target.value,
                                    )
                                }
                                placeholder="e.g., Petron San Vicente"
                            />
                            <InputError message={form.errors.fuel_station} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="fuel_receipt_no">
                                Receipt / invoice no.
                            </Label>
                            <Input
                                id="fuel_receipt_no"
                                value={form.data.fuel_receipt_no}
                                onChange={(event) =>
                                    form.setData(
                                        'fuel_receipt_no',
                                        event.target.value,
                                    )
                                }
                                placeholder="Optional receipt or invoice number"
                            />
                            <InputError
                                message={form.errors.fuel_receipt_no}
                            />
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="fuel_receipt">Receipt attachment (JPG/PNG/PDF)</Label>
                            <Input
                                id="fuel_receipt"
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                onChange={(e) => form.setData('fuel_receipt', e.target.files?.[0] ?? null)}
                            />
                            <InputError message={form.errors.fuel_receipt} />
                            {tripTicket?.fuel_receipt_url && !form.data.remove_fuel_receipt && (
                                <div className="flex items-center gap-2 text-xs">
                                    <a href={tripTicket.fuel_receipt_url} target="_blank" rel="noreferrer" className="underline underline-offset-2">View current receipt</a>
                                    <label className="flex items-center gap-1">
                                        <Checkbox checked={form.data.remove_fuel_receipt} onCheckedChange={(c) => form.setData('remove_fuel_receipt', c === true)} />
                                        Remove receipt
                                    </label>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-md border bg-muted/40 p-3">
                        <p className="text-xs tracking-wide text-muted-foreground uppercase">
                            Estimated fuel requirement
                        </p>
                        <p className="text-xl font-semibold">
                            {estimated > 0 ? `${estimated.toFixed(2)} L` : '—'}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {distance > 0
                                ? `${distance.toFixed(2)} km${
                                      form.data.round_trip
                                          ? ' each way (round trip)'
                                          : ' one way'
                                  } ÷ ${
                                      efficiency > 0
                                          ? `${efficiency.toFixed(2)} km/L`
                                          : 'no efficiency on file'
                                  }`
                                : 'Enter the distance and the fuel efficiency to compute the requirement.'}
                        </p>
                        {estimatedCost > 0 && (
                            <p className="mt-2 text-sm font-medium text-foreground">
                                Estimated fuel cost: PHP {estimatedCost.toFixed(2)}
                            </p>
                        )}
                    </div>
                </section>
            </div>

            <div className="space-y-4">
                <section className="space-y-3 rounded-xl border p-4">
                    <h3 className="text-base font-semibold">Itinerary</h3>
                    {(travelOrder.itineraries ?? []).length > 0 ? (
                        <ol className="space-y-3">
                            {(travelOrder.itineraries ?? []).map((leg) => (
                                <li
                                    key={leg.id}
                                    className="border-l-2 pl-3 text-sm"
                                >
                                    <p className="font-medium">
                                        {leg.from_location?.name ?? '—'} →{' '}
                                        {leg.to_location?.name ?? '—'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {formatDateRange(
                                            leg.date_from,
                                            leg.date_to,
                                        )}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No itinerary entries on this travel order.
                        </p>
                    )}
                </section>

                <div className="flex flex-col gap-2">
                    <Button onClick={submit} disabled={form.processing}>
                        {form.processing ? (
                            <Fuel className="size-4 animate-pulse" />
                        ) : (
                            <Save className="size-4" />
                        )}
                        {tripTicket ? 'Save changes' : 'Save'}
                    </Button>
                    <Button asChild variant="outline">
                        <Link href={cancelUrl}>
                            <ArrowLeft className="size-4" />
                            Cancel
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    );
}
