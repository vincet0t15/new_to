import { Head, router } from '@inertiajs/react';
import { ArrowRightLeft, Search } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';

import Pagination from '@/components/pagination';
import { TravelOrderStatusBadge } from '@/components/travel-orders/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import type { TravelOrder } from '@/types/travel-order';
import type { KeyboardEvent } from 'react';

type Props = {
    travelOrders: PaginatedDataResponse<TravelOrder>;
    filters: { search?: string; status?: string };
};

export default function AdminTravelOrdersIndex({
    travelOrders,
    filters,
}: Props) {
    const [reassignOrder, setReassignOrder] = useState<TravelOrder | null>(
        null,
    );
    const [targetId, setTargetId] = useState('');
    const [remarks, setRemarks] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const params: Record<string, string> = {};
        if (filters.search) params.search = filters.search;
        if (filters.status) params.status = filters.status;
        router.get(admin.travelOrders.index().url, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const openReassign = (order: TravelOrder) => {
        setReassignOrder(order);
        setTargetId('');
        setRemarks('');
    };

    const closeReassign = () => {
        setReassignOrder(null);
        setTargetId('');
        setRemarks('');
    };

    const submitReassign = () => {
        if (!reassignOrder || !targetId) return;
        setProcessing(true);
        router.post(
            admin.travelOrders.reassign(reassignOrder.id).url,
            { to_user_id: Number(targetId), remarks },
            {
                preserveScroll: true,
                onSuccess: () => closeReassign(),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const candidates = reassignOrder?.reassign_candidates ?? [];

    return (
        <>
            <Head title="Travel Order Register" />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Travel Order Register"
                    description="All travel orders across the organization."
                />
                <div className="relative w-full sm:w-[250px]">
                    <Label htmlFor="search" className="sr-only">
                        Search
                    </Label>
                    <Input
                        id="search"
                        placeholder="Search..."
                        className="w-full pl-8"
                        defaultValue={filters.search ?? ''}
                        onKeyDown={handleSearchKeyDown}
                    />
                    <Search className="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 opacity-50 select-none" />
                </div>
                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Control No.</TableHead>
                                <TableHead>Purpose</TableHead>
                                <TableHead>Employee</TableHead>
                                <TableHead>Office</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[120px] text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {travelOrders.data.length > 0 ? (
                                travelOrders.data.map((order) => (
                                    <TableRow key={order.id}>
                                        <TableCell className="font-medium">
                                            {order.control_no ??
                                                order.to_number ??
                                                `#${order.id}`}
                                        </TableCell>
                                        <TableCell className="max-w-[240px] truncate">
                                            {order.purpose ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {order.employee?.full_name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {order.office?.office_name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <TravelOrderStatusBadge
                                                status={order.status}
                                            />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {order.status === 'submitted' &&
                                                (order.reassign_candidates
                                                    ?.length ?? 0) > 0 && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openReassign(order)
                                                        }
                                                    >
                                                        <ArrowRightLeft className="size-4" />
                                                        Reassign
                                                    </Button>
                                                )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No travel orders found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
                <Pagination data={travelOrders} />

                <Dialog
                    open={reassignOrder !== null}
                    onOpenChange={(open) => {
                        if (!open) closeReassign();
                    }}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>
                                Reassign travel order{' '}
                                {reassignOrder?.control_no ??
                                    reassignOrder?.to_number ??
                                    `#${reassignOrder?.id}`}
                            </DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                Rescue a stranded document by handing its
                                current step to a new holder. The document stays
                                at the same step; only the person holding it
                                changes. This is recorded in the audit trail.
                            </p>
                            <div className="space-y-2">
                                <Label htmlFor="reassign-target">
                                    New holder
                                </Label>
                                <select
                                    id="reassign-target"
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    value={targetId}
                                    onChange={(event) =>
                                        setTargetId(event.target.value)
                                    }
                                >
                                    <option value="">Choose a person</option>
                                    {candidates.map((candidate) => (
                                        <option
                                            key={candidate.id}
                                            value={candidate.id}
                                        >
                                            {candidate.name} (
                                            {candidate.username})
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="reassign-remarks">
                                    Remarks (optional)
                                </Label>
                                <Input
                                    id="reassign-remarks"
                                    value={remarks}
                                    onChange={(event) =>
                                        setRemarks(event.target.value)
                                    }
                                    placeholder="Reason for reassignment"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={closeReassign}
                                disabled={processing}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={submitReassign}
                                disabled={processing || !targetId}
                            >
                                {processing ? 'Reassigning…' : 'Reassign'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

AdminTravelOrdersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Travel Order Register',
            href: admin.travelOrders.index().url,
        },
    ],
};
