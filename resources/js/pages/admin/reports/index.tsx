import { Head, router } from '@inertiajs/react';
import {
    Building2,
    Download,
    FileBarChart2,
    RotateCcw,
    Search,
    SlidersHorizontal,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { DashboardPanel } from '@/components/dashboard/dashboard-panel';
import { EmptyState } from '@/components/dashboard/empty-state';
import { SectionHeading } from '@/components/dashboard/section-heading';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
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
import type { TravelOrderStatus } from '@/types/travel-order';

type Filters = {
    search?: string;
    status?: string;
    office_id?: string;
    date_from?: string;
    date_to?: string;
};

type Props = {
    summary: { total: number; by_status: Record<string, number> };
    by_office: { office_name: string; total: number }[];
    offices: { id: number; office_name: string }[];
    filters: Filters;
};

type FilterValues = {
    search: string;
    status: string;
    office_id: string;
    date_from: string;
    date_to: string;
};

const EMPTY_FILTERS: FilterValues = {
    search: '',
    status: '',
    office_id: '',
    date_from: '',
    date_to: '',
};

const STATUS_LABELS: Record<TravelOrderStatus, string> = {
    draft: 'Draft',
    submitted: 'In progress',
    approved: 'Approved',
    returned: 'Returned for revision',
    cancelled: 'Cancelled',
    completed: 'Completed',
    rejected: 'Rejected',
};

const STATUS_DOTS: Record<TravelOrderStatus, string> = {
    draft: 'bg-slate-400 dark:bg-slate-500',
    submitted: 'bg-sky-500',
    approved: 'bg-emerald-600',
    returned: 'bg-amber-500',
    cancelled: 'bg-rose-500',
    completed: 'bg-emerald-700',
    rejected: 'bg-rose-700',
};

const STATUS_OPTIONS = [
    { value: '', label: 'All statuses' },
    ...Object.entries(STATUS_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

function compact(values: FilterValues): Record<string, string> {
    return Object.fromEntries(
        Object.entries(values).filter(([, value]) => value !== ''),
    );
}

function share(value: number, total: number): number {
    return total > 0 ? Math.round((value / total) * 100) : 0;
}

export default function AdminReportsIndex({
    summary,
    by_office,
    offices,
    filters,
}: Props) {
    const [values, setValues] = useState<FilterValues>({
        search: filters.search ?? '',
        status: filters.status ?? '',
        office_id: filters.office_id ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    const applied = compact({
        search: filters.search ?? '',
        status: filters.status ?? '',
        office_id: filters.office_id ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });
    const appliedCount = Object.keys(applied).length;

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(admin.reports.index().url, compact(values), {
            preserveState: true,
            preserveScroll: true,
            only: ['summary', 'by_office', 'filters'],
        });
    };

    const clearFilters = () => {
        setValues(EMPTY_FILTERS);
        router.get(
            admin.reports.index().url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                only: ['summary', 'by_office', 'filters'],
            },
        );
    };

    const statusRows = Object.entries(summary.by_status)
        .sort(([, a], [, b]) => b - a)
        .map(([status, count]) => {
            const key = status as TravelOrderStatus;

            return {
                status: key,
                label: STATUS_LABELS[key] ?? status,
                count,
            };
        });

    const dateRange =
        filters.date_from || filters.date_to
            ? `${filters.date_from || 'Earliest'} – ${filters.date_to || 'Latest'}`
            : 'All recorded dates';

    return (
        <>
            <Head title="Reports" />
            <div className="w-full space-y-6 p-4 sm:p-6">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-0.5">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Travel Order Reports
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Counts across the organization, narrowed by the
                            filters you apply.
                        </p>
                    </div>
                    <Button asChild variant="outline" className="self-start">
                        <a href={admin.reports.export({ query: applied }).url}>
                            <Download />
                            Export CSV
                        </a>
                    </Button>
                </header>

                <section
                    aria-labelledby="report-filters-heading"
                    className="rounded-xl border border-border/80 bg-card p-5 text-card-foreground shadow-xs"
                >
                    <div className="mb-4 flex flex-wrap items-start justify-between gap-2">
                        <div className="flex items-start gap-3">
                            <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border border-border/70 bg-muted/50 text-muted-foreground">
                                <SlidersHorizontal
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </span>
                            <div>
                                <h2
                                    id="report-filters-heading"
                                    className="text-sm font-semibold tracking-tight"
                                >
                                    Filters
                                </h2>
                                <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                    Applies to both the summary below and the
                                    exported CSV.
                                </p>
                            </div>
                        </div>
                        {appliedCount > 0 && (
                            <span className="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">
                                {appliedCount} active
                            </span>
                        )}
                    </div>

                    <form onSubmit={applyFilters}>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                            <div className="space-y-2 md:col-span-2 xl:col-span-2">
                                <Label htmlFor="report_search">Search</Label>
                                <div className="relative">
                                    <Input
                                        id="report_search"
                                        value={values.search}
                                        onChange={(event) =>
                                            setValues((current) => ({
                                                ...current,
                                                search: event.target.value,
                                            }))
                                        }
                                        placeholder="Purpose, destination, TO or control no."
                                        className="pl-8"
                                    />
                                    <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="report_status">Status</Label>
                                <Combobox
                                    id="report_status"
                                    value={values.status}
                                    onValueChange={(status) =>
                                        setValues((current) => ({
                                            ...current,
                                            status,
                                        }))
                                    }
                                    options={STATUS_OPTIONS}
                                    placeholder="All statuses"
                                    searchPlaceholder="Search status..."
                                    emptyMessage="No statuses found."
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="report_office">Office</Label>
                                <Combobox
                                    id="report_office"
                                    value={values.office_id}
                                    onValueChange={(office_id) =>
                                        setValues((current) => ({
                                            ...current,
                                            office_id,
                                        }))
                                    }
                                    options={[
                                        { value: '', label: 'All offices' },
                                        ...offices.map((office) => ({
                                            value: String(office.id),
                                            label: office.office_name,
                                        })),
                                    ]}
                                    placeholder="All offices"
                                    searchPlaceholder="Search office..."
                                    emptyMessage="No offices found."
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="report_date_from">
                                    Created from
                                </Label>
                                <Input
                                    id="report_date_from"
                                    type="date"
                                    value={values.date_from}
                                    onChange={(event) =>
                                        setValues((current) => ({
                                            ...current,
                                            date_from: event.target.value,
                                        }))
                                    }
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="report_date_to">
                                    Created to
                                </Label>
                                <Input
                                    id="report_date_to"
                                    type="date"
                                    value={values.date_to}
                                    onChange={(event) =>
                                        setValues((current) => ({
                                            ...current,
                                            date_to: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                        </div>

                        <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-border/70 pt-4">
                            <p className="text-xs text-muted-foreground">
                                Dates follow when each order was created, not
                                its travel dates.
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={clearFilters}
                                >
                                    <RotateCcw />
                                    Clear
                                </Button>
                                <Button type="submit">
                                    <Search />
                                    Apply filters
                                </Button>
                            </div>
                        </div>
                    </form>
                </section>

                <section aria-labelledby="report-summary-heading">
                    <SectionHeading
                        id="report-summary-heading"
                        title="Summary"
                        description={`${summary.total} travel order${
                            summary.total === 1 ? '' : 's'
                        } match the selected filters. ${dateRange}.`}
                    />
                    <div className="grid gap-4 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)]">
                        <StatCard
                            label="Matching orders"
                            value={summary.total}
                            description="Counted from travel-order records"
                            icon={FileBarChart2}
                            tone="success"
                        />
                        <DashboardPanel
                            title="Status breakdown"
                            description="Every matching order counted once, at its current status."
                            icon={FileBarChart2}
                        >
                            {statusRows.length > 0 ? (
                                <ul className="divide-y divide-border/70">
                                    {statusRows.map((row) => (
                                        <li
                                            key={row.status}
                                            className="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0"
                                        >
                                            <span
                                                aria-hidden="true"
                                                className={`size-2 shrink-0 rounded-full ${STATUS_DOTS[row.status]}`}
                                            />
                                            <span className="w-28 shrink-0 truncate text-sm sm:w-40">
                                                {row.label}
                                            </span>
                                            <span
                                                aria-hidden="true"
                                                className="h-1.5 min-w-8 flex-1 overflow-hidden rounded-full bg-muted"
                                            >
                                                <span
                                                    className="block h-full rounded-full bg-emerald-600/80 dark:bg-emerald-500/80"
                                                    style={{
                                                        width: `${share(row.count, summary.total)}%`,
                                                    }}
                                                />
                                            </span>
                                            <span className="w-12 shrink-0 text-right text-sm font-medium tabular-nums">
                                                {row.count}
                                            </span>
                                            <span className="hidden w-10 shrink-0 text-right text-xs text-muted-foreground tabular-nums sm:block">
                                                {share(
                                                    row.count,
                                                    summary.total,
                                                )}
                                                %
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <EmptyState
                                    icon={FileBarChart2}
                                    title="No orders to summarize"
                                    description="No travel orders match these filters. Clear them or widen the date range to see counts."
                                    action={
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={clearFilters}
                                        >
                                            Clear filters
                                        </Button>
                                    }
                                />
                            )}
                        </DashboardPanel>
                    </div>
                </section>

                <section aria-labelledby="report-offices-heading">
                    <SectionHeading
                        id="report-offices-heading"
                        title="By office"
                        description="Offices ranked by their share of matching travel orders."
                    />
                    <DashboardPanel
                        title="Office distribution"
                        description="Each bar is the office's share of the filtered total."
                        icon={Building2}
                        contentClassName="p-0"
                        footer={
                            <p className="text-[11px] leading-5 text-muted-foreground">
                                Counts reflect the filing office recorded on
                                each order and update with the filters above.
                            </p>
                        }
                    >
                        {by_office.length > 0 ? (
                            <div className="max-h-[30rem] overflow-y-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="hover:bg-transparent">
                                            <TableHead className="pl-5 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Office
                                            </TableHead>
                                            <TableHead className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Share
                                            </TableHead>
                                            <TableHead className="pr-5 text-right text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Orders
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {by_office.map((row) => (
                                            <TableRow key={row.office_name}>
                                                <TableCell className="pl-5 font-medium">
                                                    {row.office_name}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-3">
                                                        <span
                                                            aria-hidden="true"
                                                            className="h-1.5 min-w-12 flex-1 overflow-hidden rounded-full bg-muted"
                                                        >
                                                            <span
                                                                className="block h-full rounded-full bg-emerald-600/80 dark:bg-emerald-500/80"
                                                                style={{
                                                                    width: `${share(row.total, summary.total)}%`,
                                                                }}
                                                            />
                                                        </span>
                                                        <span className="w-10 text-right text-xs text-muted-foreground tabular-nums">
                                                            {share(
                                                                row.total,
                                                                summary.total,
                                                            )}
                                                            %
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="pr-5 text-right font-medium tabular-nums">
                                                    {row.total}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        ) : (
                            <EmptyState
                                icon={Building2}
                                title="No offices to report"
                                description="No office has a travel order matching the current filters."
                                action={
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={clearFilters}
                                    >
                                        Clear filters
                                    </Button>
                                }
                            />
                        )}
                    </DashboardPanel>
                </section>
            </div>
        </>
    );
}

AdminReportsIndex.layout = {
    breadcrumbs: [{ title: 'Reports', href: admin.reports.index().url }],
};
