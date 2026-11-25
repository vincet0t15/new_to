import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { TableEmptyState } from '@/components/table-empty-state';
import { Button } from '@/components/ui/button';
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
import admin from '@/routes/admin';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { TravelOrderAction } from '@/types/travel-order';

type Props = {
    actions: PaginatedDataResponse<TravelOrderAction>;
    filters?: {
        search?: string;
        event?: string;
        date_from?: string;
        date_to?: string;
    };
    events?: string[];
};

export default function AdminAuditIndex({
    actions,
    filters = {},
    events = [],
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [event, setEvent] = useState(filters.event ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const query = {
        search: search || undefined,
        event: event || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    };

    const submit = (submitEvent: FormEvent<HTMLFormElement>) => {
        submitEvent.preventDefault();
        router.get(admin.audit.index().url, query, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const reset = () => {
        setSearch('');
        setEvent('');
        setDateFrom('');
        setDateTo('');
        router.get(admin.audit.index().url, undefined, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Audit Log" />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Audit Log"
                    description="Immutable trail of every action taken on travel orders."
                />
                <form
                    onSubmit={submit}
                    className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2 xl:grid-cols-5"
                >
                    <div className="space-y-2 xl:col-span-2">
                        <Label htmlFor="audit_search">Search</Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="audit_search"
                                className="pl-9"
                                value={search}
                                onChange={(changeEvent) =>
                                    setSearch(changeEvent.target.value)
                                }
                                placeholder="Search audits..."
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="audit_event">Event</Label>
                        <select
                            id="audit_event"
                            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            value={event}
                            onChange={(changeEvent) =>
                                setEvent(changeEvent.target.value)
                            }
                        >
                            <option value="">All events</option>
                            {events.map((eventName) => (
                                <option key={eventName} value={eventName}>
                                    {eventName}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="audit_date_from">Date from</Label>
                        <Input
                            id="audit_date_from"
                            type="date"
                            max="9999-12-31"
                            value={dateFrom}
                            onChange={(changeEvent) =>
                                setDateFrom(changeEvent.target.value)
                            }
                            placeholder="YYYY-MM-DD"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="audit_date_to">Date to</Label>
                        <Input
                            id="audit_date_to"
                            type="date"
                            max="9999-12-31"
                            value={dateTo}
                            onChange={(changeEvent) =>
                                setDateTo(changeEvent.target.value)
                            }
                            placeholder="YYYY-MM-DD"
                        />
                    </div>
                    <div className="flex items-end gap-2 sm:col-span-2 xl:col-span-5">
                        <Button type="submit">Apply filters</Button>
                        <Button type="button" variant="outline" onClick={reset}>
                            Reset
                        </Button>
                    </div>
                </form>
                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Travel Order</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Actor</TableHead>
                                <TableHead>Remarks</TableHead>
                                <TableHead>When</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {actions.data.length > 0 ? (
                                actions.data.map((action) => (
                                    <TableRow key={action.id}>
                                        <TableCell>{action.id}</TableCell>
                                        <TableCell>
                                            {action.travelOrder
                                                ? (action.travelOrder
                                                      .control_no ??
                                                  action.travelOrder
                                                      .to_number ??
                                                  `#${action.travelOrder.id}`)
                                                : '—'}
                                        </TableCell>
                                        <TableCell>{action.event}</TableCell>
                                        <TableCell>
                                            {action.actor?.name ?? '—'}
                                        </TableCell>
                                        <TableCell className="max-w-[240px] truncate">
                                            {action.remarks ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {action.created_at ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableEmptyState
                                    colSpan={6}
                                    title="No audit entries found"
                                    description="Try a different search term."
                                />
                            )}
                        </TableBody>
                    </Table>
                </div>
                <Pagination data={actions} />
            </div>
        </>
    );
}

AdminAuditIndex.layout = {
    breadcrumbs: [{ title: 'Audit Log', href: admin.audit.index().url }],
};
