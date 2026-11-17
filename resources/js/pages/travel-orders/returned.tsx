import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Eye, Pencil, Search, Send } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
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

export default function TravelOrdersReturned({ travelOrders, filters }: Props) {
    const { data, setData } = useForm({ search: filters.search ?? '' });

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        router.get(
            travelOrdersRoutes.returned().url,
            data.search ? { search: data.search } : undefined,
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Returned for Revision" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            router.visit(travelOrdersRoutes.index().url)
                        }
                    >
                        <ArrowLeft className="size-4" />
                        Back
                    </Button>
                    <Heading
                        title="Returned for Revision"
                        description="Travel orders sent back to you. Edit and resubmit."
                    />
                </div>

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

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Control No.</TableHead>
                                <TableHead>Returned By</TableHead>
                                <TableHead>Returned At</TableHead>
                                <TableHead>Remarks</TableHead>
                                <TableHead>Revision</TableHead>
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
                                        <TableCell>
                                            {order.returnedBy?.name ?? '—'}
                                            {order.returned_from_step
                                                ? ` — ${order.returned_from_step}`
                                                : ''}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {order.returned_at ?? '—'}
                                        </TableCell>
                                        <TableCell className="max-w-[240px] truncate">
                                            {order.return_remarks ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {order.revision_number}
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
                                            <Can permission="travel-orders.resubmit">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-amber-600"
                                                    onClick={() =>
                                                        router.visit(
                                                            travelOrdersRoutes.edit(
                                                                order,
                                                            ).url,
                                                        )
                                                    }
                                                    title="Resubmit"
                                                >
                                                    <Send />
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
                                        No travel orders returned for revision.
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

TravelOrdersReturned.layout = {
    breadcrumbs: [
        {
            title: 'Returned for Revision',
            href: travelOrdersRoutes.returned().url,
        },
    ],
};
