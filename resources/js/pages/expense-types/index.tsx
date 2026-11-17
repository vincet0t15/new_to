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
import expenseTypesRoutes from '@/routes/expense-types';
import type { ExpenseType } from '@/types/expense-type';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { CreateExpenseTypeDialog } from './create';
import { DeleteExpenseTypeDialog } from './delete';
import { EditExpenseTypeDialog } from './edit';

type ExpenseTypesIndexProps = {
    expenseTypes: PaginatedDataResponse<ExpenseType>;
    filters: {
        search?: string;
    };
};

export default function ExpenseTypesIndex({
    expenseTypes,
    filters,
}: ExpenseTypesIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selectedExpenseType, setSelectedExpenseType] =
        useState<ExpenseType | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleEdit = (expenseType: ExpenseType) => {
        setSelectedExpenseType(expenseType);
        setEditOpen(true);
    };

    const handleDelete = (expenseType: ExpenseType) => {
        setSelectedExpenseType(expenseType);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            expenseTypesRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Expenses Authorized" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Expenses Authorized"
                    description="Create, update, and manage the expense categories that can be claimed on a travel order."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="expense-types.create">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Expense Type
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search expense types..."
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
                                    Flags
                                </TableHead>
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
                            {expenseTypes.data.length > 0 ? (
                                expenseTypes.data.map((expenseType) => (
                                    <TableRow key={expenseType.id}>
                                        <TableCell className="font-medium">
                                            {expenseType.name}
                                        </TableCell>
                                        <TableCell>
                                            {expenseType.description || (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap justify-center gap-1">
                                                {expenseType.is_default && (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-transparent bg-slate-100 text-slate-700"
                                                    >
                                                        Default
                                                    </Badge>
                                                )}
                                                {expenseType.is_transportation && (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-transparent bg-amber-100 text-amber-700"
                                                    >
                                                        Transportation
                                                    </Badge>
                                                )}
                                                {!expenseType.is_default &&
                                                    !expenseType.is_transportation && (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-center">
                                            {expenseType.is_active ? (
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
                                            {expenseType.usage_count > 0 ? (
                                                <Badge
                                                    variant="outline"
                                                    className="border-transparent bg-blue-100 text-blue-700"
                                                >
                                                    {expenseType.usage_count} in
                                                    use
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Can permission="expense-types.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    onClick={() =>
                                                        handleEdit(expenseType)
                                                    }
                                                    title="Edit expense type"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            <Can permission="expense-types.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() =>
                                                        handleDelete(
                                                            expenseType,
                                                        )
                                                    }
                                                    title={
                                                        expenseType.usage_count >
                                                        0
                                                            ? 'In use — cannot be deleted'
                                                            : 'Delete expense type'
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
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No expense types found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={expenseTypes} />

                {createOpen && (
                    <CreateExpenseTypeDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                    />
                )}

                {editOpen && selectedExpenseType && (
                    <EditExpenseTypeDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        expenseType={selectedExpenseType}
                    />
                )}

                {deleteOpen && selectedExpenseType && (
                    <DeleteExpenseTypeDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        expenseType={selectedExpenseType}
                    />
                )}
            </div>
        </>
    );
}

ExpenseTypesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Settings',
            href: '/admin/settings',
        },
        {
            title: 'Expenses Authorized',
            href: expenseTypesRoutes.index().url,
        },
    ],
};
