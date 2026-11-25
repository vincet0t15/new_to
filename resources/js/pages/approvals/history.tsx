import { Head, router } from '@inertiajs/react';
import { Eye, History } from 'lucide-react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { TableEmptyState } from '@/components/table-empty-state';
import { TravelOrderStatusBadge } from '@/components/travel-orders/status-badge';
import { Badge } from '@/components/ui/badge';
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
import type { PaginatedDataResponse } from '@/types/pagination';
import type { ApprovalHistoryItem } from '@/types/travel-order';

const actionClasses: Record<string, string> = {
    approved:
        'border-emerald-300 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
    acknowledged:
        'border-emerald-300 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
    returned:
        'border-amber-300 bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
    forwarded:
        'border-indigo-300 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200',
    received: 'border-slate-300 text-slate-700 dark:text-slate-200',
    pending:
        'border-blue-300 bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200',
    closed: 'border-slate-300 text-slate-500 dark:text-slate-400',
};

type Props = {
    historyItems: PaginatedDataResponse<ApprovalHistoryItem>;
    portal: string;
};

export default function ApprovalHistory({ historyItems, portal }: Props) {
    const title =
        portal === 'department-head' ? 'Department Head History' : 'History';

    return (
        <>
            <Head title={title} />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title={title}
                    description="Every travel order that reached your inbox, including the ones you already acted on."
                />

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Control No.</TableHead>
                                <TableHead>Employee</TableHead>
                                <TableHead>Purpose</TableHead>
                                <TableHead>Destination</TableHead>
                                <TableHead>Step</TableHead>
                                <TableHead>Your action</TableHead>
                                <TableHead>When</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {historyItems.data.length > 0 ? (
                                historyItems.data.map((item) => (
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
                                        <TableCell className="text-sm">
                                            {item.step_label ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={
                                                    actionClasses[
                                                        item.your_action_event ??
                                                            ''
                                                    ] ?? ''
                                                }
                                            >
                                                {item.your_action}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDateTime(item.action_at)}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <TravelOrderStatusBadge
                                                    status={item.status}
                                                />
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDate(item.date_from)}{' '}
                                                    – {formatDate(item.date_to)}
                                                </span>
                                            </div>
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
                                    colSpan={9}
                                    icon={History}
                                    title="No processing history yet"
                                    description="Orders you approve, return, or forward will be recorded here."
                                />
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={historyItems} />
            </div>
        </>
    );
}

ApprovalHistory.layout = {
    breadcrumbs: [{ title: 'History', href: '#' }],
};
