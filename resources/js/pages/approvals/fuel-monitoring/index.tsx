import { Head, router } from '@inertiajs/react';
import { Download, Fuel, Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/date-format';
import type { PaginatedDataResponse } from '@/types';

type OfficeOption = { id: number; office_name: string };

type FuelTicket = {
    id: number;
    ticket_no: string | null;
    status: string;
    vehicle_plate_no: string;
    vehicle_type: string | null;
    fuel_type: string | null;
    distance_km: string;
    liters: string;
    fuel_consumed: string | null;
    fuel_price_per_liter: string | null;
    fuel_cost: string | null;
    fuel_station: string | null;
    fuel_receipt_no: string | null;
    created_at: string | null;
    travel_order?: {
        id: number;
        control_no: string | null;
        to_number: string | null;
        purpose: string | null;
        office?: { id: number; office_name: string } | null;
    } | null;
    driver?: { id: number; full_name: string } | null;
};

type Props = {
    summary: {
        total_tickets: number;
        estimated_liters: number;
        actual_liters: number;
        fuel_cost: number;
    };
    byOffice: Array<{
        office_name: string;
        total_tickets: number;
        estimated_liters: string | number;
        actual_liters: string | number;
        fuel_cost: string | number;
    }>;
    tickets: PaginatedDataResponse<FuelTicket>;
    offices: OfficeOption[];
    filters: {
        search: string;
        office_id: string;
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
    tickets,
    offices,
    filters,
    urls,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [officeId, setOfficeId] = useState(filters.office_id ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const query = {
        search: search || undefined,
        office_id: officeId || undefined,
        status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(urls.index, query, { preserveState: true, preserveScroll: true });
    };

    const exportUrl = `${urls.export}?${new URLSearchParams(
        Object.entries(query).filter(([, value]) => value !== undefined) as [string, string][],
    ).toString()}`;

    return (
        <>
            <Head title="Fuel Monitoring" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
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
                            <CardDescription>Total trip tickets</CardDescription>
                            <CardTitle className="text-2xl">{summary.total_tickets}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Estimated fuel</CardDescription>
                            <CardTitle className="text-2xl">{liters(summary.estimated_liters)}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Actual fuel consumed</CardDescription>
                            <CardTitle className="text-2xl">{liters(summary.actual_liters)}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total fuel cost</CardDescription>
                            <CardTitle className="text-2xl">{money(summary.fuel_cost)}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <div className="space-y-2 xl:col-span-2">
                                <Label htmlFor="search">Search</Label>
                                <div className="relative">
                                    <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                    <Input
                                        id="search"
                                        className="pl-9"
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Ticket, plate, office purpose, receipt..."
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label>Office</Label>
                                <Select value={officeId || 'all'} onValueChange={(value) => setOfficeId(value === 'all' ? '' : value)}>
                                    <SelectTrigger><SelectValue placeholder="All offices" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All offices</SelectItem>
                                        {offices.map((office) => (
                                            <SelectItem key={office.id} value={String(office.id)}>
                                                {office.office_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Status</Label>
                                <Select value={status || 'all'} onValueChange={(value) => setStatus(value === 'all' ? '' : value)}>
                                    <SelectTrigger><SelectValue placeholder="All statuses" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All statuses</SelectItem>
                                        <SelectItem value="issued">Issued</SelectItem>
                                        <SelectItem value="completed">Completed</SelectItem>
                                        <SelectItem value="cancelled">Cancelled</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="date_from">Date from</Label>
                                <Input id="date_from" type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="date_to">Date to</Label>
                                <Input id="date_to" type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                            </div>
                            <div className="flex items-end gap-2 xl:col-span-4">
                                <Button type="submit">Apply filters</Button>
                                <Button type="button" variant="outline" onClick={() => {
                                    setSearch(''); setOfficeId(''); setStatus(''); setDateFrom(''); setDateTo('');
                                    router.get(urls.index);
                                }}>Reset</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Fuel by office</CardTitle>
                            <CardDescription>Where fuel is being used.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {byOffice.length > 0 ? byOffice.map((row) => (
                                <div key={row.office_name} className="rounded-lg border p-3">
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="font-medium">{row.office_name}</p>
                                        <span className="text-muted-foreground text-xs">{row.total_tickets} ticket(s)</span>
                                    </div>
                                    <div className="text-muted-foreground mt-2 grid grid-cols-2 gap-2 text-xs">
                                        <span>Est: {liters(row.estimated_liters)}</span>
                                        <span>Actual: {liters(row.actual_liters)}</span>
                                        <span className="col-span-2 font-medium text-foreground">Cost: {money(row.fuel_cost)}</span>
                                    </div>
                                </div>
                            )) : (
                                <p className="text-muted-foreground text-sm">No fuel records found.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="min-w-0">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Fuel className="size-4 text-muted-foreground" />
                                Trip ticket fuel records
                            </CardTitle>
                            <CardDescription>Estimated vs actual fuel and cost per trip ticket.</CardDescription>
                        </CardHeader>
                        <CardContent className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Ticket / TO</TableHead>
                                        <TableHead>Office</TableHead>
                                        <TableHead>Vehicle / Driver</TableHead>
                                        <TableHead className="text-right">Est. L</TableHead>
                                        <TableHead className="text-right">Actual L</TableHead>
                                        <TableHead className="text-right">Cost</TableHead>
                                        <TableHead>Station / Receipt</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tickets.data.length > 0 ? tickets.data.map((ticket) => (
                                        <TableRow key={ticket.id}>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <p className="font-medium">{ticket.ticket_no ?? `#${ticket.id}`}</p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {ticket.travel_order?.control_no ?? ticket.travel_order?.to_number ?? `TO #${ticket.travel_order?.id}`}
                                                    </p>
                                                    <p className="text-muted-foreground max-w-56 truncate text-xs" title={ticket.travel_order?.purpose ?? ''}>
                                                        {ticket.travel_order?.purpose ?? '—'}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>{ticket.travel_order?.office?.office_name ?? '—'}</TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <p>{ticket.vehicle_plate_no}</p>
                                                    <p className="text-muted-foreground text-xs">{ticket.driver?.full_name ?? 'No driver'}</p>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">{liters(ticket.liters)}</TableCell>
                                            <TableCell className="text-right tabular-nums">{liters(ticket.fuel_consumed)}</TableCell>
                                            <TableCell className="text-right tabular-nums">{money(ticket.fuel_cost)}</TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <p>{ticket.fuel_station ?? '—'}</p>
                                                    <p className="text-muted-foreground text-xs">{ticket.fuel_receipt_no ?? 'No receipt'}</p>
                                                    <p className="text-muted-foreground text-xs">{formatDateTime(ticket.created_at)}</p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-muted-foreground h-24 text-center">
                                                No fuel records found for the selected filters.
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
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })}
                                        >
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
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