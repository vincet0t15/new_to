import { router, useForm } from '@inertiajs/react';
import { Clock, MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import travelOrders from '@/routes/travel-orders';
import { formatDate } from '@/lib/date-format';
import type {
    TravelOrder,
    TravelOrderItinerary,
    TravelOrderShowOptions,
} from '@/types';

type Props = {
    travelOrder: TravelOrder;
    options: TravelOrderShowOptions;
    canManage: boolean;
};

type ItineraryFormData = {
    date_from: string;
    date_to: string;
    departure_time: string;
    arrival_time: string;
    from_location_id: string;
    to_location_id: string;
    activity: string;
    reason: string;
    transportation_mode_id: string;
    transportation_fare: string;
    transportation_fare_is_auto: boolean;
    per_diem: string;
    per_diem_is_auto: boolean;
    registration_fee: string;
};

const emptyForm = (travelOrder: TravelOrder): ItineraryFormData => ({
    date_from: travelOrder.date_from?.slice(0, 10) ?? '',
    date_to: travelOrder.date_to?.slice(0, 10) ?? '',
    departure_time: '',
    arrival_time: '',
    from_location_id:
        travelOrder.origin_id !== null ? String(travelOrder.origin_id) : '',
    to_location_id:
        travelOrder.destination_id !== null
            ? String(travelOrder.destination_id)
            : '',
    activity: '',
    reason: '',
    transportation_mode_id: '',
    transportation_fare: '',
    transportation_fare_is_auto: false,
    per_diem: '',
    per_diem_is_auto: false,
    registration_fee: '',
});

const formFromRow = (entry: TravelOrderItinerary): ItineraryFormData => ({
    date_from: entry.date_from.slice(0, 10),
    date_to: entry.date_to.slice(0, 10),
    departure_time: entry.departure_time?.slice(0, 5) ?? '',
    arrival_time: entry.arrival_time?.slice(0, 5) ?? '',
    from_location_id:
        entry.from_location_id !== null ? String(entry.from_location_id) : '',
    to_location_id:
        entry.to_location_id !== null ? String(entry.to_location_id) : '',
    activity: entry.activity,
    reason: entry.reason ?? '',
    transportation_mode_id:
        entry.transportation_mode_id !== null
            ? String(entry.transportation_mode_id)
            : '',
    transportation_fare: entry.transportation_fare ?? '',
    transportation_fare_is_auto: entry.transportation_fare_is_auto,
    per_diem: entry.per_diem ?? '',
    per_diem_is_auto: entry.per_diem_is_auto,
    registration_fee: entry.registration_fee ?? '',
});

function money(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value));
}

function lineTotal(entry: TravelOrderItinerary): number {
    return (
        Number(entry.transportation_fare ?? 0) +
        Number(entry.per_diem ?? 0) +
        Number(entry.registration_fee ?? 0)
    );
}

function formatTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    const [hours, minutes] = value.split(':').map(Number);
    const date = new Date();
    date.setHours(hours, minutes, 0, 0);

    return date.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
}

export function TravelOrderItineraryPanel({
    travelOrder,
    options,
    canManage,
}: Props) {
    const itineraries = travelOrder.itineraries ?? [];
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<TravelOrderItinerary | null>(null);
    const [pendingRemove, setPendingRemove] =
        useState<TravelOrderItinerary | null>(null);

    const form = useForm<ItineraryFormData>(emptyForm(travelOrder));

    const locationChoices = options.locations
        .filter((location) => location.is_active !== false)
        .map((location) => ({
            value: String(location.id),
            label: location.name,
        }));

    const modeChoices = options.transportationModes
        .filter((mode) => mode.is_active !== false)
        .map((mode) => ({ value: String(mode.id), label: mode.name }));

    const openCreate = () => {
        setEditing(null);
        form.setData(emptyForm(travelOrder));
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (entry: TravelOrderItinerary) => {
        setEditing(entry);
        form.setData(formFromRow(entry));
        form.clearErrors();
        setOpen(true);
    };

    const submit = () => {
        const onSuccess = () => {
            setOpen(false);
            setEditing(null);
        };

        if (editing !== null) {
            form.put(
                travelOrders.itineraries.update([travelOrder.id, editing.id])
                    .url,
                { preserveScroll: true, onSuccess },
            );

            return;
        }

        form.post(travelOrders.itineraries.store(travelOrder.id).url, {
            preserveScroll: true,
            onSuccess,
        });
    };

    const confirmRemove = () => {
        if (!pendingRemove) {
            return;
        }

        const entry = pendingRemove;
        setPendingRemove(null);
        router.delete(
            travelOrders.itineraries.destroy([travelOrder.id, entry.id]).url,
            { preserveScroll: true },
        );
    };

    const totals = itineraries.reduce(
        (carry, entry) => ({
            fare: carry.fare + Number(entry.transportation_fare ?? 0),
            perDiem: carry.perDiem + Number(entry.per_diem ?? 0),
            registration:
                carry.registration + Number(entry.registration_fee ?? 0),
        }),
        { fare: 0, perDiem: 0, registration: 0 },
    );

    return (
        <section className="space-y-4 rounded-xl border p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="text-base font-semibold">
                        Itinerary of travel
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        The places, dates, and estimated cost of each leg.
                    </p>
                </div>
                {canManage && (
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="size-4" />
                        Add itinerary
                    </Button>
                )}
            </div>

            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Place</TableHead>
                            <TableHead>Time</TableHead>
                            <TableHead>Activity</TableHead>
                            <TableHead>Means</TableHead>
                            <TableHead className="text-right">Fare</TableHead>
                            <TableHead className="text-right">
                                Per diem
                            </TableHead>
                            <TableHead className="text-right">
                                Registration
                            </TableHead>
                            <TableHead className="text-right">Total</TableHead>
                            {canManage && (
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            )}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {itineraries.length > 0 ? (
                            itineraries.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDate(entry.date_from)}
                                        {entry.date_to !== entry.date_from
                                            ? ` – ${formatDate(entry.date_to)}`
                                            : ''}
                                    </TableCell>
                                    <TableCell className="min-w-[180px] whitespace-normal">
                                        <div className="flex items-start gap-1.5">
                                            <MapPin className="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                                            <span>
                                                {entry.from_location?.name ??
                                                    '—'}{' '}
                                                →{' '}
                                                {entry.to_location?.name ?? '—'}
                                            </span>
                                        </div>
                                        {entry.reason && (
                                            <p className="mt-1 pl-5 text-xs text-muted-foreground">
                                                {entry.reason}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        <div className="flex items-center gap-1.5">
                                            <Clock className="size-3.5 text-muted-foreground" />
                                            {formatTime(entry.departure_time)}
                                            {entry.arrival_time
                                                ? ` – ${formatTime(entry.arrival_time)}`
                                                : ''}
                                        </div>
                                    </TableCell>
                                    <TableCell className="max-w-[280px] min-w-[200px] break-words whitespace-normal">
                                        {entry.activity}
                                    </TableCell>
                                    <TableCell>
                                        {entry.transportation_mode?.name ? (
                                            <span className="inline-flex items-center gap-1">
                                                {entry.transportation_mode.name}
                                                {entry.transportation_fare_is_auto && (
                                                    <Badge
                                                        variant="outline"
                                                        className="text-[10px]"
                                                    >
                                                        auto
                                                    </Badge>
                                                )}
                                            </span>
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {money(entry.transportation_fare)}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {money(entry.per_diem)}
                                        {entry.per_diem_is_auto && (
                                            <span className="ml-1 text-[10px] text-muted-foreground">
                                                auto
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {money(entry.registration_fee)}
                                    </TableCell>
                                    <TableCell className="text-right font-medium tabular-nums">
                                        {money(lineTotal(entry))}
                                    </TableCell>
                                    {canManage && (
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    onClick={() =>
                                                        openEdit(entry)
                                                    }
                                                    title="Edit"
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-destructive"
                                                    onClick={() =>
                                                        setPendingRemove(entry)
                                                    }
                                                    title="Remove"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={canManage ? 10 : 9}
                                    className="h-24 text-center text-muted-foreground"
                                >
                                    <div className="flex flex-col items-center gap-2">
                                        <MapPin className="size-6 text-muted-foreground/50" />
                                        No itinerary entries yet.
                                        {canManage && (
                                            <span className="text-xs">
                                                Add the first leg so approvers
                                                can see where the trip goes.
                                            </span>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {itineraries.length > 0 && (
                <div className="flex flex-wrap justify-end gap-6 text-sm">
                    <span>
                        Fare:{' '}
                        <strong className="tabular-nums">
                            {money(totals.fare)}
                        </strong>
                    </span>
                    <span>
                        Per diem:{' '}
                        <strong className="tabular-nums">
                            {money(totals.perDiem)}
                        </strong>
                    </span>
                    <span>
                        Registration:{' '}
                        <strong className="tabular-nums">
                            {money(totals.registration)}
                        </strong>
                    </span>
                    <span>
                        Grand total:{' '}
                        <strong className="tabular-nums">
                            {money(
                                totals.fare +
                                    totals.perDiem +
                                    totals.registration,
                            )}
                        </strong>
                    </span>
                </div>
            )}

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Edit itinerary entry' : 'Add itinerary'}
                        </DialogTitle>
                        <DialogDescription>
                            The automatic per diem and fare are resolved from
                            Travel Setup when you save.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="date_from">Date from *</Label>
                            <Input
                                id="date_from"
                                type="date"
                                value={form.data.date_from}
                                onChange={(event) =>
                                    form.setData(
                                        'date_from',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.date_from} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="date_to">Date to *</Label>
                            <Input
                                id="date_to"
                                type="date"
                                value={form.data.date_to}
                                onChange={(event) =>
                                    form.setData('date_to', event.target.value)
                                }
                            />
                            <InputError message={form.errors.date_to} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="departure_time">
                                Departure time *
                            </Label>
                            <Input
                                id="departure_time"
                                type="time"
                                value={form.data.departure_time}
                                onChange={(event) =>
                                    form.setData(
                                        'departure_time',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.departure_time} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="arrival_time">Arrival time</Label>
                            <Input
                                id="arrival_time"
                                type="time"
                                value={form.data.arrival_time}
                                onChange={(event) =>
                                    form.setData(
                                        'arrival_time',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.arrival_time} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="from_location_id">From *</Label>
                            <Combobox
                                id="from_location_id"
                                value={form.data.from_location_id}
                                onValueChange={(value) =>
                                    form.setData('from_location_id', value)
                                }
                                options={locationChoices}
                                placeholder="Select origin"
                                searchPlaceholder="Search location..."
                            />
                            <InputError
                                message={form.errors.from_location_id}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="to_location_id">To *</Label>
                            <Combobox
                                id="to_location_id"
                                value={form.data.to_location_id}
                                onValueChange={(value) =>
                                    form.setData('to_location_id', value)
                                }
                                options={locationChoices}
                                placeholder="Select destination"
                                searchPlaceholder="Search location..."
                            />
                            <InputError message={form.errors.to_location_id} />
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="activity">Activity *</Label>
                            <Textarea
                                id="activity"
                                rows={3}
                                value={form.data.activity}
                                onChange={(event) =>
                                    form.setData('activity', event.target.value)
                                }
                                placeholder="e.g., Attend the municipal planning session"
                            />
                            <InputError message={form.errors.activity} />
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="reason">Reason</Label>
                            <Textarea
                                id="reason"
                                rows={3}
                                value={form.data.reason}
                                onChange={(event) =>
                                    form.setData('reason', event.target.value)
                                }
                                placeholder="Optional reason for this leg"
                            />
                            <InputError message={form.errors.reason} />
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="transportation_mode_id">
                                Means of transportation
                            </Label>
                            <Combobox
                                id="transportation_mode_id"
                                value={form.data.transportation_mode_id}
                                onValueChange={(value) =>
                                    form.setData(
                                        'transportation_mode_id',
                                        value,
                                    )
                                }
                                options={modeChoices}
                                placeholder="Select a mode"
                                searchPlaceholder="Search mode..."
                            />
                            <InputError
                                message={form.errors.transportation_mode_id}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="transportation_fare">
                                Transportation fare
                            </Label>
                            <Input
                                id="transportation_fare"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.transportation_fare}
                                disabled={form.data.transportation_fare_is_auto}
                                onChange={(event) =>
                                    form.setData(
                                        'transportation_fare',
                                        event.target.value,
                                    )
                                }
                                placeholder={
                                    form.data.transportation_fare_is_auto
                                        ? 'Computed from the mode'
                                        : '0.00'
                                }
                            />
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={
                                        form.data.transportation_fare_is_auto
                                    }
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'transportation_fare_is_auto',
                                            checked === true,
                                        )
                                    }
                                />
                                Compute from the transportation setup
                            </label>
                            <InputError
                                message={form.errors.transportation_fare}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="per_diem">Per diem</Label>
                            <Input
                                id="per_diem"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.per_diem}
                                disabled={form.data.per_diem_is_auto}
                                onChange={(event) =>
                                    form.setData('per_diem', event.target.value)
                                }
                                placeholder={
                                    form.data.per_diem_is_auto
                                        ? 'Computed from the per diem setup'
                                        : '0.00'
                                }
                            />
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.data.per_diem_is_auto}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'per_diem_is_auto',
                                            checked === true,
                                        )
                                    }
                                />
                                Compute from the per diem setup
                            </label>
                            <InputError message={form.errors.per_diem} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="registration_fee">
                                Registration fee
                            </Label>
                            <Input
                                id="registration_fee"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.registration_fee}
                                onChange={(event) =>
                                    form.setData(
                                        'registration_fee',
                                        event.target.value,
                                    )
                                }
                                placeholder="0.00"
                            />
                            <InputError
                                message={form.errors.registration_fee}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setOpen(false)}
                            disabled={form.processing}
                        >
                            Cancel
                        </Button>
                        <Button onClick={submit} disabled={form.processing}>
                            {editing ? 'Save changes' : 'Save'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={pendingRemove !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingRemove(null);
                    }
                }}
                title="Remove itinerary entry?"
                description="The entry will be removed from this travel order. This cannot be undone."
                confirmLabel="Remove entry"
                destructive
                onConfirm={confirmRemove}
            />
        </section>
    );
}
