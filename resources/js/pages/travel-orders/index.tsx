import { Head, router, useForm } from '@inertiajs/react';
import { Eye, Pencil, Plus, Search, Trash2 } from 'lucide-react';
// no state needed
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import travelOrdersRoutes from '@/routes/travel-orders';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { TravelOrder } from '@/types/travel-order';
import type { KeyboardEvent } from 'react';

type Props = {
    travelOrders: PaginatedDataResponse<TravelOrder>;
    filters: { search?: string };
};

export default function TravelOrdersIndex({ travelOrders, filters }: Props) {
    const { data, setData } = useForm({ search: filters.search ?? '' });

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        router.get(
            travelOrdersRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="My Travel Orders" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="My Travel Orders"
                    description="Create, track, and manage your travel orders."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="travel-orders.create">
                        <Button
                            onClick={() =>
                                router.visit(travelOrdersRoutes.create().url)
                            }
                        >
                            <Plus />
                            New Travel Order
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search travel orders..."
                            className="w-full pl-8"
                            value={data.search}
                            onChange={(event) =>
                                setData('search', event.target.value)
                            }
                            onKeyDown={handleSearchKeyDown}
                        />
                        <Search className="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 opacity-50 select-none" />
                    </div>
                </div>

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Control No.</TableHead>
                                <TableHead>Purpose</TableHead>
                                <TableHead>Destination</TableHead>
                                <TableHead>Dates</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
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
                                            {order.destination ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {order.date_from} → {order.date_to}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {order.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-blue-600"
                                                onClick={() =>
                                                    router.visit(
                                                        travelOrdersRoutes.show(
                                                            order,
                                                        ).url,
                                                    )
                                                }
                                                title="View"
                                            >
                                                <Eye />
                                            </Button>
                                            <Can permission="travel-orders.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600"
                                                    onClick={() =>
                                                        router.visit(
                                                            travelOrdersRoutes.edit(
                                                                order,
                                                            ).url,
                                                        )
                                                    }
                                                    title="Edit"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            <Can permission="travel-orders.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600"
                                                    onClick={() => {
                                                        if (
                                                            confirm(
                                                                'Delete this travel order?',
                                                            )
                                                        ) {
                                                            router.delete(
                                                                travelOrdersRoutes.destroy(
                                                                    order,
                                                                ).url,
                                                            );
                                                        }
                                                    }}
                                                    title="Delete"
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </Can>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No travel orders yet. Create your first
                                        one.
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

TravelOrdersIndex.layout = {
    breadcrumbs: [
        { title: 'My Travel Orders', href: travelOrdersRoutes.index().url },
    ],
};
