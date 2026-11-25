import { Head, router } from '@inertiajs/react';
import { Download, Eye, Fuel, Printer, Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CustomComboBox } from '@/components/CustomComboBox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/date-format';
import travelOrders from '@/routes/travel-orders';
import type { PaginatedDataResponse } from '@/types';

type OfficeOption = { id: number; office_name: string };
type FuelTypeOption = { id: number; name: string };

type FuelTicket = {
    id: number;
    ticket_no: string | null;
    status: string;
    vehicle_plate_no: string;
    vehicle_type: string | null;
    fuel_type: string | null;
    fuel_type_record?: { id: number; name: string } | null;
    distance_km: string;
    liters: string;
    fuel_consumed: string | null;
    fuel_price_per_liter: string | null;
    fuel_cost: string | null;
    fuel_station: string | null;
    fuel_receipt_no: string | null;
    fuel_receipt_url?: string | null;
    created_at: string | null;
    travel_order?: {
        id: number;
        control_no: string | null;
        to_number: string | null;
        purpose: string | null;
        office?: { id: number; office_name: string } | null;
    } | null;
    fuel_source_office?: { id: number; office_name: string } | null;
    driver?: { id: number; full_name: string } | null;
};

type Props = {
    summary: {
        total_tickets: number;
        estimated_liters: number;
        actual_liters: number;
        fuel_cost: number;
        allocated_liters: number;
        allocated_amount: number;
        remaining_liters: number;
        remaining_amount: number;
    };
    byOffice: Array<{
        office_id: number;
        office_name: string;
        total_tickets: number;
        estimated_liters: string | number;
        actual_liters: string | number;
        fuel_cost: string | number;
        allocated_liters: string | number;
        allocated_amount: string | number;
        remaining_liters: string | number;
        remaining_amount: string | number;
        fuel_types?: Array<{
            office_id: number;
            office_name: string;
            fuel_type: string;
            total_tickets: number;
            estimated_liters: string | number;
            actual_liters: string | number;
            fuel_cost: string | number;
            allocated_liters: string | number;
            allocated_amount: string | number;
            remaining_liters: string | number;
            remaining_amount: string | number;
        }>;
    }>;
    byOfficeFuelType: Array<{
        office_id: number;
        office_name: string;
        fuel_type: string;
        total_tickets: number;
        estimated_liters: string | number;
        actual_liters: string | number;
        fuel_cost: string | number;
        allocated_liters: string | number;
        allocated_amount: string | number;
        remaining_liters: string | number;
        remaining_amount: string | number;
    }>;
    borrowedByOffice: Array<{
        borrower_office_name: string;
        source_office_name: string;
        fuel_type: string;
        total_tickets: number;
        estimated_liters: string | number;
        actual_liters: string | number;
        fuel_cost: string | number;
    }>;
    tickets: PaginatedDataResponse<FuelTicket>;
    offices: OfficeOption[];
    fuelTypes: FuelTypeOption[];
    filters: {
        search: string;
        office_id: string;
        fuel_type_id: string;
        borrowed_only: string;
        date_from: string;
        date_to: string;
        status: string;
    };
    urls: {
        index: string;
        export: string;
    };
};

function money(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return `PHP ${Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function liters(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return `${Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} L`;
}

export default function FuelMonitoringIndex({
    summary,
    byOffice,
    borrowedByOffice,
    tickets,
    offices,
    fuelTypes,
    filters,
    urls,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [officeId, setOfficeId] = useState(filters.office_id ?? '');
    const [fuelTypeId, setFuelTypeId] = useState(filters.fuel_type_id ?? '');
    const [borrowedOnly, setBorrowedOnly] = useState(
        filters.borrowed_only === '1',
    );
    const [status, setStatus] = useState(filters.status ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const query = {
        search: search || undefined,
        office_id: officeId || undefined,
        fuel_type_id: fuelTypeId || undefined,
        borrowed_only: borrowedOnly ? '1' : undefined,
        status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(urls.index, query, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const exportUrl = `${urls.export}?${new URLSearchParams(
        Object.entries(query).filter(([, value]) => value !== undefined) as [
            string,
            string,
        ][],
    ).toString()}`;

    return (
        <>
            <Head title="Fuel Monitoring" />
            <div className="flex h-full w-full min-w-0 flex-1 flex-col gap-6 rounded-xl p-4 sm:p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Fuel Monitoring"
                        description="Track estimated fuel, actual consumption, and fuel cost by office and trip ticket."
                    />
                    <Button variant="outline" asChild>
                        <a href={exportUrl}>
                            <Download className="size-4" />
                            Export CSV
                        </a>
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Allocated fuel</CardDescription>
                            <CardTitle className="text-2xl">
                                {liters(summary.allocated_liters)}
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                {money(summary.allocated_amount)}
                            </p>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Actual consumed</CardDescription>
                            <CardTitle className="text-2xl">
                                {liters(summary.actual_liters)}
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                {money(summary.fuel_cost)} · completed tickets
                                only
                            </p>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Remaining balance</CardDescription>
                            <CardTitle
                                className={`text-2xl ${summary.remaining_liters < 0 || summary.remaining_amount < 0 ? 'text-destructive' : ''}`}
                            >
                                {liters(summary.remaining_liters)}
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                {money(summary.remaining_amount)} remaining
                            </p>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>
                                Trip tickets / Estimated
                            </CardDescription>
                            <CardTitle className="text-2xl">
                                {summary.total_tickets}
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Est. {liters(summary.estimated_liters)} ·
                                issued/active estimate
                            </p>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid gap-4 md:grid-cols-2 xl:grid-cols-6"
                        >
                            <div className="space-y-2 xl:col-span-2">
                                <Label htmlFor="search">Search</Label>
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        className="pl-9"
                                        value={search}
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        placeholder="Ticket, plate, purpose, receipt, fuel type..."
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="fuel_office_filter">
                                    Office
                                </Label>
                                <CustomComboBox
                                    id="fuel_office_filter"
                                    items={[
                                        { value: '', label: 'All offices' },
                                        ...offices.map((office) => ({
                                            value: String(office.id),
                                            label: office.office_name,
                                        })),
                                    ]}
                                    value={officeId}
                                    placeholder="All offices"
                                    onSelect={(v) => setOfficeId(v ?? '')}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="fuel_type_filter">
                                    Fuel type
                                </Label>
                                <CustomComboBox
                                    id="fuel_type_filter"
                                    items={[
                                        { value: '', label: 'All fuel types' },
                                        ...fuelTypes.map((ft) => ({
                                            value: String(ft.id),
                                            label: ft.name,
                                        })),
                                    ]}
                                    value={fuelTypeId}
                                    placeholder="All fuel types"
                                    onSelect={(v) => setFuelTypeId(v ?? '')}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="fuel_status_filter">
                                    Status
                                </Label>
                                <CustomComboBox
                                    id="fuel_status_filter"
                                    items={[
                                        { value: '', label: 'All statuses' },
                                        { value: 'issued', label: 'Issued' },
                                        {
                                            value: 'completed',
                                            label: 'Completed',
                                        },
                                        {
                                            value: 'cancelled',
                                            label: 'Cancelled',
                                        },
                                    ]}
                                    value={status}
                                    placeholder="All statuses"
                                    onSelect={(v) => setStatus(v ?? '')}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="date_from">Date from</Label>
                                <Input
                                    id="date_from"
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) =>
                                        setDateFrom(e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="date_to">Date to</Label>
                                <Input
                                    id="date_to"
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                />
                            </div>
                            <div className="flex items-end gap-2 xl:col-span-6">
                                <label className="mr-auto flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={borrowedOnly}
                                        onChange={(e) =>
                                            setBorrowedOnly(e.target.checked)
                                        }
                                    />
                                    Borrowed fuel only
                                </label>
                                <Button type="submit">Apply filters</Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setSearch('');
                                        setOfficeId('');
                                        setFuelTypeId('');
                                        setBorrowedOnly(false);
                                        setStatus('');
                                        setDateFrom('');
                                        setDateTo('');
                                        router.get(urls.index);
                                    }}
                                >
                                    Reset
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Fuel by office
                            </CardTitle>
                            <CardDescription>
                                Where fuel is being used.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {byOffice.length > 0 ? (
                                byOffice.map((row) => (
                                    <div
                                        key={row.office_name}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <button
                                                type="button"
                                                className="text-left font-medium underline-offset-2 hover:underline"
                                                onClick={() => {
                                                    const id = String(
                                                        row.office_id,
                                                    );
                                                    setOfficeId(id);
                                                    router.get(
                                                        urls.index,
                                                        {
                                                            ...query,
                                                            office_id: id,
                                                        },
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }}
                                            >
                                                {row.office_name}
                                            </button>
                                            <span className="text-xs text-muted-foreground">
                                                {row.total_tickets} ticket(s)
                                            </span>
                                        </div>
                                        <div className="mt-2 grid grid-cols-2 gap-2 text-xs text-muted-foreground">
                                            <span>
                                                Allocated:{' '}
                                                {liters(row.allocated_liters)}
                                            </span>
                                            <span>
                                                Actual:{' '}
                                                {liters(row.actual_liters)}
                                            </span>
                                            <span>
                                                Allocated:{' '}
                                                {money(row.allocated_amount)}
                                            </span>
                                            <span>
                                                Cost: {money(row.fuel_cost)}
                                            </span>
                                            <span
                                                className={`col-span-2 font-medium ${Number(row.remaining_liters) < 0 || Number(row.remaining_amount) < 0 ? 'text-destructive' : 'text-foreground'}`}
                                            >
                                                Remaining:{' '}
                                                {liters(row.remaining_liters)} /{' '}
                                                {money(row.remaining_amount)}
                                            </span>
                                        </div>
                                        {row.fuel_types &&
                                            row.fuel_types.length > 0 && (
                                                <div className="mt-3 space-y-1 border-t pt-2">
                                                    {row.fuel_types.map(
                                                        (ft) => (
                                                            <div
                                                                key={`${row.office_id}-${ft.fuel_type}`}
                                                                className="flex items-center justify-between gap-2 text-[11px]"
                                                            >
                                                                <span className="text-muted-foreground">
                                                                    {
                                                                        ft.fuel_type
                                                                    }
                                                                </span>
                                                                <span
                                                                    className={
                                                                        Number(
                                                                            ft.remaining_liters,
                                                                        ) < 0
                                                                            ? 'text-destructive'
                                                                            : 'text-muted-foreground'
                                                                    }
                                                                >
                                                                    {liters(
                                                                        ft.remaining_liters,
                                                                    )}{' '}
                                                                    left
                                                                </span>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            )}
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No fuel records found.
                                </p>
                            )}

                            <div className="border-t pt-4">
                                <p className="mb-2 text-sm font-medium">
                                    Borrowed fuel
                                </p>
                                {borrowedByOffice.length > 0 ? (
                                    borrowedByOffice.map((row) => (
                                        <div
                                            key={`${row.borrower_office_name}-${row.source_office_name}-${row.fuel_type}`}
                                            className="mb-2 rounded-md border p-2 text-xs last:mb-0"
                                        >
                                            <p className="font-medium">
                                                {row.borrower_office_name}{' '}
                                                borrowed from{' '}
                                                {row.source_office_name}
                                            </p>
                                            <p className="mt-1 text-muted-foreground">
                                                {row.fuel_type} ·{' '}
                                                {row.total_tickets} ticket(s) ·
                                                Est.{' '}
                                                {liters(row.estimated_liters)} ·
                                                Actual{' '}
                                                {liters(row.actual_liters)} ·{' '}
                                                {money(row.fuel_cost)}
                                            </p>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-xs text-muted-foreground">
                                        No borrowed fuel records in selected
                                        filters.
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="min-w-0">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Fuel className="size-4 text-muted-foreground" />
                                Trip ticket fuel records
                            </CardTitle>
                            <CardDescription>
                                Estimated vs actual fuel and cost per trip
                                ticket.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Ticket / TO</TableHead>
                                        <TableHead>Fuel source</TableHead>
                                        <TableHead>Vehicle / Driver</TableHead>
                                        <TableHead className="text-right">
                                            Est. L
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actual L
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Cost
                                        </TableHead>
                                        <TableHead>Station / Receipt</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tickets.data.length > 0 ? (
                                        tickets.data.map((ticket) => (
                                            <TableRow key={ticket.id}>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <p className="font-medium">
                                                            {ticket.ticket_no ??
                                                                `#${ticket.id}`}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {ticket.travel_order
                                                                ?.control_no ??
                                                                ticket
                                                                    .travel_order
                                                                    ?.to_number ??
                                                                `TO #${ticket.travel_order?.id}`}
                                                        </p>
                                                        <p
                                                            className="max-w-56 truncate text-xs text-muted-foreground"
                                                            title={
                                                                ticket
                                                                    .travel_order
                                                                    ?.purpose ??
                                                                ''
                                                            }
                                                        >
                                                            {ticket.travel_order
                                                                ?.purpose ??
                                                                '—'}
                                                        </p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <p>
                                                            {ticket
                                                                .fuel_source_office
                                                                ?.office_name ??
                                                                ticket
                                                                    .travel_order
                                                                    ?.office
                                                                    ?.office_name ??
                                                                '—'}
                                                        </p>
                                                        {ticket
                                                            .fuel_source_office
                                                            ?.office_name &&
                                                            ticket.travel_order
                                                                ?.office
                                                                ?.office_name &&
                                                            ticket
                                                                .fuel_source_office
                                                                .office_name !==
                                                                ticket
                                                                    .travel_order
                                                                    .office
                                                                    .office_name && (
                                                                <>
                                                                    <p className="text-xs font-medium text-amber-700 dark:text-amber-400">
                                                                        Borrowed
                                                                        from
                                                                        this
                                                                        office
                                                                    </p>
                                                                    <p className="text-xs text-muted-foreground">
                                                                        By TO
                                                                        office:{' '}
                                                                        {
                                                                            ticket
                                                                                .travel_order
                                                                                .office
                                                                                .office_name
                                                                        }
                                                                    </p>
                                                                </>
                                                            )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <p>
                                                            {
                                                                ticket.vehicle_plate_no
                                                            }
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {ticket.driver
                                                                ?.full_name ??
                                                                'No driver'}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {ticket
                                                                .fuel_type_record
                                                                ?.name ??
                                                                ticket.fuel_type ??
                                                                '—'}
                                                        </p>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {liters(ticket.liters)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {liters(
                                                        ticket.fuel_consumed,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {money(ticket.fuel_cost)}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <p>
                                                            {ticket.fuel_station ??
                                                                '—'}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {ticket.fuel_receipt_no ??
                                                                'No receipt'}
                                                        </p>
                                                        {ticket.fuel_receipt_url && (
                                                            <a
                                                                href={
                                                                    ticket.fuel_receipt_url
                                                                }
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="text-xs text-primary underline-offset-2 hover:underline"
                                                            >
                                                                View receipt
                                                            </a>
                                                        )}
                                                        <p className="text-xs text-muted-foreground">
                                                            {formatDateTime(
                                                                ticket.created_at,
                                                            )}
                                                        </p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-2">
                                                        {ticket.travel_order
                                                            ?.id ? (
                                                            <>
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        router.visit(
                                                                            travelOrders.tripTickets.show(
                                                                                [
                                                                                    ticket
                                                                                        .travel_order!
                                                                                        .id,
                                                                                    ticket.id,
                                                                                ],
                                                                            )
                                                                                .url,
                                                                        )
                                                                    }
                                                                >
                                                                    <Eye className="size-4" />
                                                                    View
                                                                </Button>
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    asChild
                                                                >
                                                                    <a
                                                                        href={
                                                                            travelOrders.tripTickets.print(
                                                                                [
                                                                                    ticket
                                                                                        .travel_order!
                                                                                        .id,
                                                                                    ticket.id,
                                                                                ],
                                                                            )
                                                                                .url
                                                                        }
                                                                        target="_blank"
                                                                        rel="noreferrer"
                                                                    >
                                                                        <Printer className="size-4" />
                                                                        Print
                                                                    </a>
                                                                </Button>
                                                            </>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground">
                                                                —
                                                            </span>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={8}
                                                className="h-24 text-center text-muted-foreground"
                                            >
                                                No fuel records found for the
                                                selected filters.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>

                            {tickets.links?.length > 3 && (
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {tickets.links.map((link, index) => (
                                        <Button
                                            key={`${link.label}-${index}`}
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() =>
                                                link.url &&
                                                router.visit(link.url, {
                                                    preserveScroll: true,
                                                    preserveState: true,
                                                })
                                            }
                                        >
                                            <span
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        </Button>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
