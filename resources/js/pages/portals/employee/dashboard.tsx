import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    FilePenLine,
    FileText,
    Inbox,
    MapPin,
    Plus,
    RotateCcw,
    Send,
} from 'lucide-react';
import { Can } from '@/components/can';
import { DashboardHeader } from '@/components/dashboard/dashboard-header';
import { DashboardLink } from '@/components/dashboard/dashboard-link';
import { DashboardPanel } from '@/components/dashboard/dashboard-panel';
import { EmptyState } from '@/components/dashboard/empty-state';
import { QuickLinks } from '@/components/dashboard/quick-links';
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
import { formatDate, formatDateTime } from '@/lib/date-format';
import travelOrdersRoutes from '@/routes/travel-orders';
import employee from '@/routes/employee';
import inbox from '@/routes/inbox';
import type { DashboardTravelOrderItem, PersonalDashboardStats } from '@/types';

type Props = {
    stats: PersonalDashboardStats;
    recentOrders: DashboardTravelOrderItem[];
    draftOrders: DashboardTravelOrderItem[];
};

export default function EmployeeDashboard({
    stats,
    recentOrders,
    draftOrders,
}: Props) {
    const { auth, unreadNotificationsCount } = usePage().props;
    const today = new Date().toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });

    return (
        <>
            <Head title="Employee Dashboard" />

            <div className="flex h-full flex-1 flex-col bg-muted/30">
                <div className="flex w-full flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                    <DashboardHeader
                        eyebrow="Employee portal"
                        title={`Welcome back, ${auth.user.name}`}
                        description="Track every official travel request you file, from draft to final approval."
                        date={today}
                    >
                        <Can permission="travel-orders.create">
                            <Button asChild>
                                <Link href={travelOrdersRoutes.create().url}>
                                    <Plus aria-hidden="true" />
                                    New travel order
                                </Link>
                            </Button>
                        </Can>
                    </DashboardHeader>

                    {stats.returned > 0 && (
                        <div
                            role="status"
                            className="flex flex-col gap-3 rounded-xl border border-amber-300/80 bg-amber-50 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/70 dark:bg-amber-950/30"
                        >
                            <div className="flex items-start gap-3">
                                <AlertTriangle
                                    aria-hidden="true"
                                    className="mt-0.5 size-4 shrink-0 text-amber-700 dark:text-amber-400"
                                />
                                <div>
                                    <p className="text-sm font-medium text-amber-950 dark:text-amber-100">
                                        {stats.returned} travel order
                                        {stats.returned === 1 ? '' : 's'}{' '}
                                        returned for revision
                                    </p>
                                    <p className="mt-0.5 text-xs leading-5 text-amber-800 dark:text-amber-200/80">
                                        Review the approver&apos;s remarks,
                                        update the details, and resubmit.
                                    </p>
                                </div>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                className="shrink-0 border-amber-300/80 bg-white/70 text-amber-950 hover:bg-white dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100 dark:hover:bg-amber-950/70"
                            >
                                <Link href={travelOrdersRoutes.returned().url}>
                                    Review returned
                                    <ArrowRight aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>
                    )}

                    <section aria-labelledby="personal-stats-heading">
                        <SectionHeading
                            id="personal-stats-heading"
                            title="Your travel orders"
                            description="A snapshot of every request you have filed."
                            action={
                                <DashboardLink
                                    href={travelOrdersRoutes.index().url}
                                >
                                    View all orders
                                </DashboardLink>
                            }
                        />
                        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <StatCard
                                label="Drafts"
                                value={stats.draft}
                                description="Still editable"
                                href={
                                    travelOrdersRoutes.index({
                                        query: { status: 'draft' },
                                    }).url
                                }
                                icon={FilePenLine}
                            />
                            <StatCard
                                label="In progress"
                                value={stats.submitted}
                                description="Submitted & awaiting approval"
                                href={
                                    travelOrdersRoutes.index({
                                        query: { status: 'submitted' },
                                    }).url
                                }
                                icon={Send}
                                tone="info"
                            />
                            <StatCard
                                label="Returned"
                                value={stats.returned}
                                description="Needs your revision"
                                href={travelOrdersRoutes.returned().url}
                                icon={RotateCcw}
                                tone="warning"
                            />
                            <StatCard
                                label="Approved"
                                value={stats.approved}
                                description="Finalized orders"
                                href={
                                    travelOrdersRoutes.index({
                                        query: { status: 'approved' },
                                    }).url
                                }
                                icon={CheckCircle2}
                                tone="success"
                            />
                        </div>
                    </section>

                    <Can permission="travel-orders.view">
                        <div className="grid items-start gap-6 xl:grid-cols-3">
                            <DashboardPanel
                                className="xl:col-span-2"
                                title="Recent travel orders"
                                description="Your five most recently updated requests."
                                icon={FileText}
                                action={
                                    <DashboardLink
                                        href={travelOrdersRoutes.index().url}
                                    >
                                        All orders
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
                                                    Travel dates
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
                                                    className="group hover:bg-muted/40"
                                                >
                                                    <TableCell className="pl-5">
                                                        <Link
                                                            href={
                                                                travelOrdersRoutes.show(
                                                                    order.id,
                                                                ).url
                                                            }
                                                            className="font-medium underline-offset-4 hover:underline"
                                                        >
                                                            {order.control_no ??
                                                                order.to_number ??
                                                                `#${order.id}`}
                                                        </Link>
                                                        <p className="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                                                            <MapPin
                                                                aria-hidden="true"
                                                                className="size-3 shrink-0"
                                                            />
                                                            <span className="truncate">
                                                                {order.destination ??
                                                                    'No destination set'}
                                                            </span>
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
                                                    <TableCell className="text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                                        {formatDate(
                                                            order.date_from,
                                                        )}{' '}
                                                        -{' '}
                                                        {formatDate(
                                                            order.date_to,
                                                        )}
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
                                        icon={FileText}
                                        title="No travel orders yet"
                                        description="Once you file a request, it will appear here with its current approval status."
                                        action={
                                            <Can permission="travel-orders.create">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={
                                                            travelOrdersRoutes.create()
                                                                .url
                                                        }
                                                    >
                                                        <Plus aria-hidden="true" />
                                                        Create your first order
                                                    </Link>
                                                </Button>
                                            </Can>
                                        }
                                    />
                                )}
                            </DashboardPanel>

                            <DashboardPanel
                                title="Shortcuts"
                                description="Jump straight to the work that needs you."
                                contentClassName="p-0"
                                footer={
                                    <p className="text-[11px] leading-5 text-muted-foreground">
                                        Drafts stay editable. Submitted orders
                                        are read-only until an approver returns
                                        them for revision.
                                    </p>
                                }
                            >
                                <QuickLinks
                                    links={[
                                        {
                                            title: 'All travel orders',
                                            description:
                                                'Search and review every request you have filed.',
                                            href: travelOrdersRoutes.index()
                                                .url,
                                            icon: FileText,
                                        },
                                        {
                                            title: 'Returned for revision',
                                            description:
                                                'Correct the items your approvers sent back, then resubmit.',
                                            href: travelOrdersRoutes.returned()
                                                .url,
                                            icon: RotateCcw,
                                            badge: stats.returned,
                                        },
                                        {
                                            title: 'Notifications',
                                            description:
                                                'Updates on your travel orders and returned requests.',
                                            href: inbox.index().url,
                                            icon: Inbox,
                                            badge: unreadNotificationsCount,
                                        },
                                    ]}
                                />
                            </DashboardPanel>
                        </div>

                        <DashboardPanel
                            title="Draft travel orders"
                            description="Requests you have saved but not yet submitted. Continue editing or delete them."
                            icon={FilePenLine}
                            action={
                                <DashboardLink
                                    href={
                                        travelOrdersRoutes.index({
                                            query: { status: 'draft' },
                                        }).url
                                    }
                                >
                                    All drafts
                                </DashboardLink>
                            }
                            contentClassName="p-0"
                        >
                            {draftOrders.length > 0 ? (
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
                                                Travel dates
                                            </TableHead>
                                            <TableHead className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Updated
                                            </TableHead>
                                            <TableHead className="pr-5 text-right text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Action
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {draftOrders.map((order) => (
                                            <TableRow
                                                key={order.id}
                                                className="group hover:bg-muted/40"
                                            >
                                                <TableCell className="pl-5">
                                                    <span className="font-medium">
                                                        {order.control_no ??
                                                            order.to_number ??
                                                            `#${order.id}`}
                                                    </span>
                                                    <p className="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                                                        <MapPin
                                                            aria-hidden="true"
                                                            className="size-3 shrink-0"
                                                        />
                                                        <span className="truncate">
                                                            {order.destination ??
                                                                'No destination set'}
                                                        </span>
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
                                                <TableCell className="text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                                    {formatDate(
                                                        order.date_from,
                                                    )}{' '}
                                                    -{' '}
                                                    {formatDate(order.date_to)}
                                                </TableCell>
                                                <TableCell className="text-sm whitespace-nowrap text-muted-foreground">
                                                    {formatDateTime(
                                                        order.updated_at,
                                                    )}
                                                </TableCell>
                                                <TableCell className="pr-5 text-right">
                                                    <Can permission="travel-orders.update">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={
                                                                    travelOrdersRoutes.edit(
                                                                        order,
                                                                    ).url
                                                                }
                                                            >
                                                                <FilePenLine aria-hidden="true" />
                                                                Continue
                                                            </Link>
                                                        </Button>
                                                    </Can>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <EmptyState
                                    icon={FilePenLine}
                                    title="No drafts saved"
                                    description="Start a new travel order and save it as a draft to continue later."
                                    action={
                                        <Can permission="travel-orders.create">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={
                                                        travelOrdersRoutes.create()
                                                            .url
                                                    }
                                                >
                                                    <Plus aria-hidden="true" />
                                                    Create travel order
                                                </Link>
                                            </Button>
                                        </Can>
                                    }
                                />
                            )}
                        </DashboardPanel>
                    </Can>
                </div>
            </div>
        </>
    );
}

EmployeeDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Employee Dashboard',
            href: employee.dashboard().url,
        },
    ],
};
