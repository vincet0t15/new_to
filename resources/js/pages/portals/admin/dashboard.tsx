import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowUpRight,
    BadgeCheck,
    CheckCircle2,
    ClipboardList,
    FileClock,
    FilePenLine,
    Fuel,
    Landmark,
    RotateCcw,
    Ticket,
    XCircle,
} from 'lucide-react';
import { DashboardHeader } from '@/components/dashboard/dashboard-header';
import { DashboardLink } from '@/components/dashboard/dashboard-link';
import { DashboardPanel } from '@/components/dashboard/dashboard-panel';
import { EmptyState } from '@/components/dashboard/empty-state';
import { SectionHeading } from '@/components/dashboard/section-heading';
import { StatCard } from '@/components/stat-card';
import { TravelOrderStatusBadge } from '@/components/travel-orders/status-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/date-format';
import admin from '@/routes/admin';
import type {
    AdminDashboardStats,
    DashboardTravelOrderItem,
    FuelAlert,
    FuelSummary,
    StalledTravelOrderItem,
} from '@/types';

type Props = {
    stats: AdminDashboardStats;
    fuelAlerts: FuelAlert[];
    fuelThresholds: { low_balance_liters: number; low_balance_amount: number };
    recentOrders: DashboardTravelOrderItem[];
    stalledOrders: StalledTravelOrderItem[];
    stalledTotal: number;
    stalledThresholdDays: number;
    fuelSummary: FuelSummary;
};

function money(value: number) {
    return `PHP ${Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function liters(value: number) {
    return `${Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} L`;
}

function registerHref(order: {
    id: number;
    control_no?: string | null;
    to_number?: string | null;
}) {
    return admin.travelOrders.index({
        query: {
            search: order.control_no ?? order.to_number ?? String(order.id),
        },
    }).url;
}

export default function AdminDashboard({
    stats,
    fuelAlerts,
    fuelThresholds,
    recentOrders,
    stalledOrders,
    stalledTotal,
    stalledThresholdDays,
    fuelSummary,
}: Props) {
    const { auth } = usePage().props;
    const today = new Date().toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });

    const totalOrders =
        stats.draft +
        stats.submitted +
        stats.returned +
        stats.approved +
        stats.completed +
        stats.cancelled +
        stats.rejected;
    const visibleAlerts = fuelAlerts.slice(0, 5);
    const cancelledOrRejected = stats.cancelled + stats.rejected;

    const orderCards = [
        {
            label: 'Draft',
            value: stats.draft,
            description: 'Not yet submitted',
            icon: FilePenLine,
            tone: 'default' as const,
            href: admin.travelOrders.index({
                query: { status: 'draft' },
            }).url,
        },
        {
            label: 'In progress',
            value: stats.submitted,
            description: 'Moving through approval',
            icon: FileClock,
            tone: 'info' as const,
            href: admin.travelOrders.index({
                query: { status: 'submitted' },
            }).url,
        },
        {
            label: 'Returned',
            value: stats.returned,
            description: 'Awaiting employee revision',
            icon: RotateCcw,
            tone: 'warning' as const,
            href: admin.travelOrders.index({
                query: { status: 'returned' },
            }).url,
        },
        {
            label: 'Approved',
            value: stats.approved,
            description: 'Authorized for travel',
            icon: CheckCircle2,
            tone: 'success' as const,
            href: admin.travelOrders.index({
                query: { status: 'approved' },
            }).url,
        },
        {
            label: 'Completed',
            value: stats.completed,
            description: 'Closed after travel',
            icon: BadgeCheck,
            tone: 'success' as const,
            href: admin.travelOrders.index({
                query: { status: 'completed' },
            }).url,
        },
        {
            label: 'Cancelled / rejected',
            value: cancelledOrRejected,
            description: 'Withdrawn or declined',
            icon: XCircle,
            tone: 'danger' as const,
        },
    ];

    return (
        <>
            <Head title="Admin Dashboard" />

            <div className="flex h-full flex-1 flex-col bg-muted/30">
                <div className="flex w-full flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                    <DashboardHeader
                        eyebrow="System administration"
                        title={`Welcome back, ${auth.user.name}`}
                        description="Monitor travel orders and fuel operations across the organization at a glance."
                        date={today}
                    >
                        <Button variant="outline" asChild>
                            <Link href={admin.fuelMonitoring.index().url}>
                                <Fuel aria-hidden="true" />
                                Fuel monitoring
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={admin.travelOrders.index().url}>
                                <ClipboardList aria-hidden="true" />
                                Travel order register
                            </Link>
                        </Button>
                    </DashboardHeader>

                    <section aria-labelledby="travel-order-pipeline-heading">
                        <SectionHeading
                            id="travel-order-pipeline-heading"
                            title="Travel order pipeline"
                            description={`${totalOrders} travel order${
                                totalOrders === 1 ? '' : 's'
                            } on record, by current stage.`}
                            action={
                                <DashboardLink
                                    href={admin.travelOrders.index().url}
                                >
                                    Open register
                                </DashboardLink>
                            }
                        />
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                            {orderCards.map((card) => (
                                <StatCard
                                    key={card.label}
                                    label={card.label}
                                    value={card.value}
                                    description={card.description}
                                    icon={card.icon}
                                    tone={card.tone}
                                    href={card.href}
                                />
                            ))}
                        </div>
                    </section>

                    <section aria-labelledby="stalled-approvals-heading">
                        <SectionHeading
                            id="stalled-approvals-heading"
                            title="Stalled approvals"
                            description={`${stalledTotal} submitted travel ${
                                stalledTotal === 1 ? 'order has' : 'orders have'
                            } been waiting at least ${stalledThresholdDays} day${
                                stalledThresholdDays === 1 ? '' : 's'
                            } for action.`}
                            action={
                                <DashboardLink
                                    href={
                                        admin.travelOrders.index({
                                            query: { status: 'submitted' },
                                        }).url
                                    }
                                >
                                    Review queue
                                </DashboardLink>
                            }
                        />
                        <DashboardPanel
                            title="Oldest waiting orders"
                            description="Open deliveries at their current step, oldest first."
                            icon={FileClock}
                            contentClassName="p-0"
                            footer={
                                <p className="text-[11px] leading-5 text-muted-foreground">
                                    Age is measured from submission. This is a
                                    monitoring hint; it does not move an order
                                    or change who may act on it.
                                </p>
                            }
                        >
                            {stalledOrders.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow className="hover:bg-transparent">
                                            <TableHead className="pl-5 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Travel order
                                            </TableHead>
                                            <TableHead className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Current holder
                                            </TableHead>
                                            <TableHead className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Step
                                            </TableHead>
                                            <TableHead className="pr-5 text-right text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Waiting
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {stalledOrders.map((order) => (
                                            <TableRow key={order.id}>
                                                <TableCell className="pl-5">
                                                    <Link
                                                        href={registerHref(
                                                            order,
                                                        )}
                                                        className="block text-sm font-medium underline-offset-4 hover:underline"
                                                    >
                                                        {order.control_no ??
                                                            order.to_number ??
                                                            `#${order.id}`}
                                                    </Link>
                                                    <span className="block max-w-[18rem] truncate text-xs text-muted-foreground">
                                                        {order.purpose ?? '—'}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="block text-sm">
                                                        {order.holder}
                                                    </span>
                                                    <span className="block text-xs text-muted-foreground">
                                                        {order.office ?? '—'}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {order.step}
                                                </TableCell>
                                                <TableCell className="pr-5 text-right text-sm font-medium tabular-nums">
                                                    {order.age_days}d
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <EmptyState
                                    icon={FileClock}
                                    title="No stalled approvals"
                                    description="Nothing has been waiting past the monitoring threshold. New submissions appear here automatically if they age."
                                />
                            )}
                        </DashboardPanel>
                    </section>

                    <section aria-labelledby="fuel-operations-heading">
                        <SectionHeading
                            id="fuel-operations-heading"
                            title="Fuel operations"
                            description="Allocations and trip ticket activity across all offices."
                            action={
                                <DashboardLink
                                    href={admin.fuelAllocations.index().url}
                                >
                                    Manage allocations
                                </DashboardLink>
                            }
                        />
                        <DashboardPanel
                            title="Fuel position"
                            description="Live operational counts from the fuel ledger and trip tickets."
                            icon={Fuel}
                            contentClassName="p-0"
                            footer={
                                <p className="text-[11px] leading-5 text-muted-foreground">
                                    Balances use actual consumption from
                                    completed trip tickets only. Issued tickets
                                    are tracked separately until completed.
                                </p>
                            }
                        >
                            <ul className="grid divide-y divide-border/70 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                                <li>
                                    <Link
                                        href={admin.fuelAllocations.index().url}
                                        className="group flex h-full flex-col gap-1 p-5 transition-colors hover:bg-muted/40 focus-visible:bg-muted/40 focus-visible:outline-none"
                                    >
                                        <span className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                            <Landmark
                                                aria-hidden="true"
                                                className="size-3.5"
                                            />
                                            Active allocations
                                        </span>
                                        <span className="text-2xl font-semibold tracking-tight tabular-nums">
                                            {fuelSummary.active_allocations}
                                        </span>
                                        <span className="mt-auto flex items-center gap-1 text-xs text-muted-foreground">
                                            Open office allocations
                                            <ArrowUpRight
                                                aria-hidden="true"
                                                className="size-3.5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                            />
                                        </span>
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={
                                            admin.fuelMonitoring.index({
                                                query: { status: 'issued' },
                                            }).url
                                        }
                                        className="group flex h-full flex-col gap-1 p-5 transition-colors hover:bg-muted/40 focus-visible:bg-muted/40 focus-visible:outline-none"
                                    >
                                        <span className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                            <Ticket
                                                aria-hidden="true"
                                                className="size-3.5"
                                            />
                                            Issued trip tickets
                                        </span>
                                        <span className="text-2xl font-semibold tracking-tight tabular-nums">
                                            {fuelSummary.issued_trip_tickets}
                                        </span>
                                        <span className="mt-auto flex items-center gap-1 text-xs text-muted-foreground">
                                            Awaiting completion
                                            <ArrowUpRight
                                                aria-hidden="true"
                                                className="size-3.5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                            />
                                        </span>
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={
                                            admin.fuelMonitoring.index({
                                                query: { status: 'completed' },
                                            }).url
                                        }
                                        className="group flex h-full flex-col gap-1 p-5 transition-colors hover:bg-muted/40 focus-visible:bg-muted/40 focus-visible:outline-none"
                                    >
                                        <span className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                            <CheckCircle2
                                                aria-hidden="true"
                                                className="size-3.5"
                                            />
                                            Completed trip tickets
                                        </span>
                                        <span className="text-2xl font-semibold tracking-tight tabular-nums">
                                            {fuelSummary.completed_trip_tickets}
                                        </span>
                                        <span className="mt-auto flex items-center gap-1 text-xs text-muted-foreground">
                                            Actual consumption posted
                                            <ArrowUpRight
                                                aria-hidden="true"
                                                className="size-3.5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                            />
                                        </span>
                                    </Link>
                                </li>
                            </ul>
                        </DashboardPanel>
                    </section>

                    <div className="grid items-start gap-6 xl:grid-cols-3">
                        <DashboardPanel
                            className="xl:col-span-2"
                            title="Recent travel orders"
                            description="The latest updates across the organization."
                            icon={ClipboardList}
                            action={
                                <DashboardLink
                                    href={admin.travelOrders.index().url}
                                >
                                    Open register
                                </DashboardLink>
                            }
                            contentClassName="p-0"
                        >
                            {recentOrders.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow className="hover:bg-transparent">
                                            <TableHead className="pl-5 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Travel order
                                            </TableHead>
                                            <TableHead className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Purpose
                                            </TableHead>
                                            <TableHead className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Status
                                            </TableHead>
                                            <TableHead className="pr-5 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Updated
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recentOrders.map((order) => (
                                            <TableRow
                                                key={order.id}
                                                className="hover:bg-muted/40"
                                            >
                                                <TableCell className="pl-5">
                                                    <Link
                                                        href={registerHref(
                                                            order,
                                                        )}
                                                        className="font-medium underline-offset-4 hover:underline"
                                                    >
                                                        {order.control_no ??
                                                            order.to_number ??
                                                            `#${order.id}`}
                                                    </Link>
                                                    <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                        {order.employee ??
                                                            'Unknown employee'}
                                                        {order.office
                                                            ? ` - ${order.office}`
                                                            : ''}
                                                    </p>
                                                </TableCell>
                                                <TableCell className="max-w-[260px]">
                                                    <p
                                                        className="truncate"
                                                        title={
                                                            order.purpose ??
                                                            undefined
                                                        }
                                                    >
                                                        {order.purpose ??
                                                            'Not specified'}
                                                    </p>
                                                </TableCell>
                                                <TableCell>
                                                    {order.status ? (
                                                        <TravelOrderStatusBadge
                                                            status={
                                                                order.status
                                                            }
                                                        />
                                                    ) : (
                                                        <span className="text-sm text-muted-foreground">
                                                            Unknown
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="pr-5 text-sm whitespace-nowrap text-muted-foreground">
                                                    {formatDateTime(
                                                        order.updated_at,
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <EmptyState
                                    icon={ClipboardList}
                                    title="No travel orders on record"
                                    description="Travel orders will appear here as soon as employees begin filing them."
                                />
                            )}
                        </DashboardPanel>

                        <DashboardPanel
                            title="Fuel balance alerts"
                            description={
                                fuelAlerts.length > 0
                                    ? `${fuelAlerts.length} balance${
                                          fuelAlerts.length === 1 ? '' : 's'
                                      } need attention.`
                                    : 'Offices at or below configured thresholds.'
                            }
                            icon={AlertTriangle}
                            action={
                                <DashboardLink
                                    href={admin.fuelMonitoring.index().url}
                                >
                                    View monitoring
                                </DashboardLink>
                            }
                            contentClassName="p-0"
                            footer={
                                <p className="text-[11px] leading-5 text-muted-foreground">
                                    Alerts trigger at{' '}
                                    {liters(fuelThresholds.low_balance_liters)}{' '}
                                    or{' '}
                                    {money(fuelThresholds.low_balance_amount)}.
                                    {fuelAlerts.length > visibleAlerts.length &&
                                        ` Showing ${visibleAlerts.length} of ${fuelAlerts.length}.`}
                                </p>
                            }
                        >
                            {visibleAlerts.length > 0 ? (
                                <ul className="divide-y divide-border/70">
                                    {visibleAlerts.map((alert) => {
                                        const negative =
                                            alert.remaining_liters < 0 ||
                                            alert.remaining_amount < 0;

                                        return (
                                            <li
                                                key={`${alert.office_id}-${alert.fuel_type}`}
                                            >
                                                <Link
                                                    href={
                                                        admin.fuelMonitoring.index(
                                                            {
                                                                query: {
                                                                    office_id:
                                                                        String(
                                                                            alert.office_id,
                                                                        ),
                                                                },
                                                            },
                                                        ).url
                                                    }
                                                    className="group flex items-start gap-3 px-5 py-3.5 transition-colors hover:bg-muted/40 focus-visible:bg-muted/40 focus-visible:outline-none"
                                                >
                                                    <span
                                                        aria-hidden="true"
                                                        className={`mt-1.5 size-2 shrink-0 rounded-full ${
                                                            negative
                                                                ? 'bg-rose-500'
                                                                : 'bg-amber-500'
                                                        }`}
                                                    />
                                                    <span className="min-w-0 flex-1">
                                                        <span className="block truncate text-sm font-medium">
                                                            {alert.office_name}
                                                        </span>
                                                        <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                                                            {alert.fuel_type} -{' '}
                                                            {negative
                                                                ? 'Over allocation'
                                                                : 'Below threshold'}
                                                        </span>
                                                    </span>
                                                    <span className="shrink-0 text-right">
                                                        <span
                                                            className={`block text-sm font-semibold tabular-nums ${
                                                                negative
                                                                    ? 'text-rose-700 dark:text-rose-400'
                                                                    : ''
                                                            }`}
                                                        >
                                                            {liters(
                                                                alert.remaining_liters,
                                                            )}
                                                        </span>
                                                        <span className="mt-0.5 block text-[11px] text-muted-foreground tabular-nums">
                                                            {money(
                                                                alert.remaining_amount,
                                                            )}
                                                        </span>
                                                    </span>
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            ) : (
                                <EmptyState
                                    icon={CheckCircle2}
                                    title="All balances are healthy"
                                    description="No office is below the configured fuel thresholds right now."
                                    className="py-10"
                                />
                            )}
                        </DashboardPanel>
                    </div>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Admin Dashboard',
            href: admin.dashboard().url,
        },
    ],
};
