import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
import fundSourcesRoutes from '@/routes/fund-sources';
import type { FundSource } from '@/types/fund-source';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { CreateFundSourceDialog } from './create';
import { DeleteFundSourceDialog } from './delete';
import { EditFundSourceDialog } from './edit';

type FundSourcesIndexProps = {
    fundSources: PaginatedDataResponse<FundSource>;
    filters: {
        search?: string;
    };
};

export default function FundSourcesIndex({
    fundSources,
    filters,
}: FundSourcesIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selected, setSelected] = useState<FundSource | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleEdit = (fundSource: FundSource) => {
        setSelected(fundSource);
        setEditOpen(true);
    };

    const handleDelete = (fundSource: FundSource) => {
        setSelected(fundSource);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            fundSourcesRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Fund Sources" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Fund Sources"
                    description="Create, update, and manage the funds a travel order can be charged against."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="fund-sources.create">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Fund Source
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search fund sources..."
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
                                <TableHead>Name</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="text-center">
                                    Status
                                </TableHead>
                                <TableHead className="text-center">
                                    Usage
                                </TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {fundSources.data.length > 0 ? (
                                fundSources.data.map((fundSource) => (
                                    <TableRow key={fundSource.id}>
                                        <TableCell className="font-medium">
                                            {fundSource.name}
                                        </TableCell>
                                        <TableCell>
                                            {fundSource.description || '—'}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            {fundSource.is_active ? (
                                                <Badge
                                                    variant="outline"
                                                    className="border-transparent bg-green-100 text-green-700"
                                                >
                                                    Active
                                                </Badge>
                                            ) : (
                                                <Badge
                                                    variant="outline"
                                                    className="border-transparent bg-muted text-muted-foreground"
                                                >
                                                    Inactive
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            {fundSource.usage_count > 0 ? (
                                                <Badge
                                                    variant="outline"
                                                    className="border-transparent bg-blue-100 text-blue-700"
                                                >
                                                    {fundSource.usage_count} in
                                                    use
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Can permission="fund-sources.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    onClick={() =>
                                                        handleEdit(fundSource)
                                                    }
                                                    title="Edit fund source"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            <Can permission="fund-sources.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() =>
                                                        handleDelete(fundSource)
                                                    }
                                                    title={
                                                        fundSource.usage_count >
                                                        0
                                                            ? 'In use — cannot be deleted'
                                                            : 'Delete fund source'
                                                    }
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
                                        colSpan={5}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No fund sources found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={fundSources} />

                {createOpen && (
                    <CreateFundSourceDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                    />
                )}

                {editOpen && selected && (
                    <EditFundSourceDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        fundSource={selected}
                    />
                )}

                {deleteOpen && selected && (
                    <DeleteFundSourceDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        fundSource={selected}
                    />
                )}
            </div>
        </>
    );
}

FundSourcesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Settings',
            href: '/admin/settings',
        },
        {
            title: 'Fund Sources',
            href: fundSourcesRoutes.index().url,
        },
    ],
};
