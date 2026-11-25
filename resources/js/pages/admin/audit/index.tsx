import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
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
    filters?: { search?: string };
};

export default function AdminAuditIndex({ actions }: Props) {
    return (
        <>
            <Head title="Audit Log" />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Audit Log"
                    description="Immutable trail of every action taken on travel orders."
                />
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
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No audit entries found.
                                    </TableCell>
                                </TableRow>
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
