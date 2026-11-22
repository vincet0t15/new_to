import { Head, router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
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
import { formatDate } from '@/lib/date-format';
import type { ProcessingDashboardStats } from '@/types';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { ApprovalInboxItem } from '@/types/travel-order';

type Props = {
    deliveries: PaginatedDataResponse<ApprovalInboxItem>;
    stats: ProcessingDashboardStats;
    portal: string;
};

export default function ApprovalInbox({ deliveries, stats, portal }: Props) {
    const title =
        portal === 'department-head'
            ? 'Department Head Inbox'
            : 'Approvals Inbox';

    return (
        <>
            <Head title={title} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title={title}
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
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(item.submitted_at)}
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
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Nothing is waiting for you right now.
                                    </TableCell>
                                </TableRow>
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
    breadcrumbs: [{ title: 'Approval Inbox', href: '#' }],
};
