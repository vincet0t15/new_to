import { Head, Link, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    History,
    Inbox,
    RotateCcw,
    UserRound,
} from 'lucide-react';
import { Can } from '@/components/can';
import { DashboardHeader } from '@/components/dashboard/dashboard-header';
import { DashboardLink } from '@/components/dashboard/dashboard-link';
import { DashboardPanel } from '@/components/dashboard/dashboard-panel';
import { EmptyState } from '@/components/dashboard/empty-state';
import { QuickLinks } from '@/components/dashboard/quick-links';
import { SectionHeading } from '@/components/dashboard/section-heading';
import { StatCard } from '@/components/stat-card';
import { DeliveryStatusBadge } from '@/components/travel-orders/status-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { APPROVAL_INBOX_LABEL } from '@/lib/approval-inbox-label';
import { formatDateTime } from '@/lib/date-format';
import departmentHead from '@/routes/department-head';
import type {
    DashboardTravelOrderItem,
    ProcessingDashboardStats,
} from '@/types';

type Props = {
    stats: ProcessingDashboardStats;
    pendingItems: DashboardTravelOrderItem[];
};

export default function DepartmentHeadDashboard({
    stats,
    pendingItems,
}: Props) {
    const { auth } = usePage().props;
    const today = new Date().toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });

    return (
        <>
            <Head title="Department Head Dashboard" />

            <div className="flex h-full flex-1 flex-col bg-muted/30">
                <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                    <DashboardHeader
                        eyebrow="Department head portal"
                        title={`Welcome back, ${auth.user.name}`}
                        description="Review travel orders assigned to your office and keep every request moving."
                        date={today}
                    >
                        <Can permission="travel-orders.view">
                            <Button asChild>
                                <Link href={departmentHead.inbox().url}>
                                    <Inbox aria-hidden="true" />
                                    Open {APPROVAL_INBOX_LABEL}
                                </Link>
                            </Button>
                        </Can>
                    </DashboardHeader>

                    <section aria-labelledby="department-stats-heading">
                        <SectionHeading
                            id="department-stats-heading"
                            title={APPROVAL_INBOX_LABEL}
                            description="Your desk at a glance."
                            action={
                                <DashboardLink
                                    href={departmentHead.history().url}
                                >
                                    Processing history
                                </DashboardLink>
                            }
                        />
                        <div className="grid gap-3 sm:grid-cols-3">
                            <StatCard
                                label="Pending"
                                value={stats.pending}
                                description="Waiting for your action"
                                href={departmentHead.inbox().url}
                                icon={Inbox}
                                tone="warning"
                            />
                            <StatCard
                                label="Processed"
                                value={stats.processed}
                                description="Orders you acted on"
                                href={departmentHead.history().url}
                                icon={CheckCircle2}
                                tone="success"
                            />
                            <StatCard
                                label="Returned"
                                value={stats.returned}
                                description="Sent back for revision"
                                href={departmentHead.history().url}
                                icon={RotateCcw}
                                tone="danger"
                            />
                        </div>
                    </section>

                    <Can permission="travel-orders.view">
                        <div className="grid items-start gap-6 xl:grid-cols-3">
                            <DashboardPanel
                                className="xl:col-span-2"
                                title="Pending items"
                                description="Latest submissions waiting for your action."
                                icon={Inbox}
                                action={
                                    <DashboardLink
                                        href={departmentHead.inbox().url}
                                    >
                                        Open {APPROVAL_INBOX_LABEL}
                                    </DashboardLink>
                                }
                                contentClassName="p-0"
                            >
                                {pendingItems.length > 0 ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="hover:bg-transparent">
                                                <TableHead className="pl-5 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                    Travel order
                                                </TableHead>
                                                <TableHead className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                    Employee
                                                </TableHead>
                                                <TableHead className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                    Purpose
                                                </TableHead>
                                                <TableHead className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                    Delivery
                                                </TableHead>
                                                <TableHead className="pr-5 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                    Submitted
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {pendingItems.map((item) => (
                                                <TableRow
                                                    key={item.id}
                                                    className="group hover:bg-muted/40"
                                                >
                                                    <TableCell className="pl-5">
                                                        <Link
                                                            href={
                                                                departmentHead.travelOrders.show(
                                                                    item.id,
                                                                ).url
                                                            }
                                                            className="font-medium underline-offset-4 hover:underline"
                                                        >
                                                            {item.control_no ??
                                                                item.to_number ??
                                                                `#${item.id}`}
                                                        </Link>
                                                        <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                            {item.office ??
                                                                'Unassigned office'}
                                                        </p>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="flex items-center gap-1.5 text-sm">
                                                            <UserRound
                                                                aria-hidden="true"
                                                                className="size-3.5 shrink-0 text-muted-foreground"
                                                            />
                                                            <span className="truncate">
                                                                {item.employee ??
                                                                    'Unknown employee'}
                                                            </span>
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="max-w-[240px]">
                                                        <p
                                                            className="truncate"
                                                            title={
                                                                item.purpose ??
                                                                undefined
                                                            }
                                                        >
                                                            {item.purpose ??
                                                                'Not specified'}
                                                        </p>
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.delivery_status ? (
                                                            <DeliveryStatusBadge
                                                                status={
                                                                    item.delivery_status
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
                                                            item.submitted_at,
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <EmptyState
                                        icon={Inbox}
                                        title="Your queue is clear"
                                        description="No travel orders are waiting at your approval step right now."
                                        action={
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={
                                                        departmentHead.history()
                                                            .url
                                                    }
                                                >
                                                    <History aria-hidden="true" />
                                                    Review processing history
                                                </Link>
                                            </Button>
                                        }
                                    />
                                )}
                            </DashboardPanel>

                            <DashboardPanel
                                title="Shortcuts"
                                description="Everything your approval desk needs."
                                contentClassName="p-0"
                                footer={
                                    <p className="text-[11px] leading-5 text-muted-foreground">
                                        An order leaves this queue as soon as
                                        you approve, return, or forward it.
                                    </p>
                                }
                            >
                                <QuickLinks
                                    links={[
                                        {
                                            title: APPROVAL_INBOX_LABEL,
                                            description:
                                                'Open the full queue of orders assigned to your office.',
                                            href: departmentHead.inbox().url,
                                            icon: Inbox,
                                            badge: stats.pending,
                                        },
                                        {
                                            title: 'Processing history',
                                            description:
                                                'Review every order that has passed through your desk.',
                                            href: departmentHead.history().url,
                                            icon: History,
                                        },
                                    ]}
                                />
                            </DashboardPanel>
                        </div>
                    </Can>
                </div>
            </div>
        </>
    );
}

DepartmentHeadDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Department Head Dashboard',
            href: departmentHead.dashboard().url,
        },
    ],
};
