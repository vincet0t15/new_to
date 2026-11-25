import { Head, router } from '@inertiajs/react';
import { Eye, Inbox } from 'lucide-react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { StatCard } from '@/components/stat-card';
import { TableEmptyState } from '@/components/table-empty-state';
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
import { formatDate } from '@/lib/date-format';
import type { ProcessingDashboardStats } from '@/types';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { ApprovalInboxItem } from '@/types/travel-order';

type Props = {
    deliveries: PaginatedDataResponse<ApprovalInboxItem>;
    stats: ProcessingDashboardStats;
    portal: string;
    stalledAfterDays: number;
};

function waitingLabel(days: number | null): string {
    if (days === null) {
        return '—';
    }

    if (days <= 0) {
        return 'Today';
    }

    return days === 1 ? '1 day' : `${days} days`;
}

export default function ApprovalInbox({
    deliveries,
    stats,
    stalledAfterDays,
}: Props) {
    return (
        <>
            <Head title={APPROVAL_INBOX_LABEL} />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title={APPROVAL_INBOX_LABEL}
                    description="Travel orders currently waiting for your action."
                />

                <section aria-label="Processing summary">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <StatCard
                            label="Pending"
                            value={stats.pending}
                            description="Waiting for your action"
                            tone="warning"
                        />
                        <StatCard
                            label="Processed"
                            value={stats.processed}
                            description="Orders you acted on"
                            tone="success"
                        />
                        <StatCard
                            label="Returned"
                            value={stats.returned}
                            description="Orders you sent back for revision"
                        />
                    </div>
                </section>

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Control No.</TableHead>
                                <TableHead>Employee</TableHead>
                                <TableHead>Purpose</TableHead>
                                <TableHead>Destination</TableHead>
                                <TableHead>Step</TableHead>
                                <TableHead>Travel dates</TableHead>
                                <TableHead>Waiting</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {deliveries.data.length > 0 ? (
                                deliveries.data.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">
                                            {item.control_no ??
                                                item.to_number ??
                                                `#${item.id}`}
                                        </TableCell>
                                        <TableCell>
                                            {item.employee ?? '—'}
                                        </TableCell>
                                        <TableCell className="max-w-[220px] truncate">
                                            {item.purpose ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {item.destination ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="text-sm">
                                                    {item.step_label ?? '—'}
                                                </span>
                                                <DeliveryStatusBadge
                                                    status={
                                                        item.delivery_status
                                                    }
                                                />
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(item.date_from)} –{' '}
                                            {formatDate(item.date_to)}
                                        </TableCell>
                                        <TableCell
                                            className={`text-sm ${
                                                item.waiting_days !== null &&
                                                item.waiting_days >=
                                                    stalledAfterDays
                                                    ? 'font-medium text-amber-700 dark:text-amber-300'
                                                    : 'text-muted-foreground'
                                            }`}
                                            title={
                                                item.submitted_at
                                                    ? `Submitted ${formatDate(item.submitted_at)}`
                                                    : undefined
                                            }
                                        >
                                            {waitingLabel(item.waiting_days)}
                                            {item.waiting_days !== null &&
                                                item.waiting_days >=
                                                    stalledAfterDays && (
                                                    <span className="ml-1.5 rounded bg-amber-100 px-1 py-0.5 text-[10px] font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                                                        Stalled
                                                    </span>
                                                )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-blue-600"
                                                onClick={() =>
                                                    router.visit(item.url)
                                                }
                                                title="Open"
                                            >
                                                <Eye />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableEmptyState
                                    colSpan={8}
                                    icon={Inbox}
                                    title="Nothing is waiting for you"
                                    description="Travel orders assigned to your step will appear here as soon as they are filed."
                                />
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={deliveries} />
            </div>
        </>
    );
}

ApprovalInbox.layout = {
    breadcrumbs: [{ title: APPROVAL_INBOX_LABEL, href: '#' }],
};
