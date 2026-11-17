import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { TravelOrderStatusBadge } from '@/components/travel-orders/status-badge';
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

    return (
        <>
            <Head title="Travel Order Register" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
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
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
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
