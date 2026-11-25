import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
import employmentTypesRoutes from '@/routes/employment-types';
import type { EmploymentType } from '@/types/employment-type';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { CreateEmploymentTypeDialog } from './create';
import { DeleteEmploymentTypeDialog } from './delete';
import { EditEmploymentTypeDialog } from './edit';

type EmploymentTypesIndexProps = {
    employmentTypes: PaginatedDataResponse<EmploymentType>;
    filters: {
        search?: string;
    };
};

export default function EmploymentTypesIndex({
    employmentTypes,
    filters,
}: EmploymentTypesIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selectedEmploymentType, setSelectedEmploymentType] =
        useState<EmploymentType | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleEdit = (employmentType: EmploymentType) => {
        setSelectedEmploymentType(employmentType);
        setEditOpen(true);
    };

    const handleDelete = (employmentType: EmploymentType) => {
        setSelectedEmploymentType(employmentType);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            employmentTypesRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Employment Types" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Employment Type Management"
                    description="Create, update, and manage the employment types positions are filed under."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="employment-types.create">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Employment Type
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            className="w-full pl-8"
                            value={data.search}
                            onChange={(event) =>
                                setData('search', event.target.value)
                            }
                            onKeyDown={handleSearchKeyDown}
                            placeholder="Search..."
                        />
                        <Search className="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 opacity-50 select-none" />
                    </div>
                </div>

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Employment Type</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>Positions</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {employmentTypes.data.length > 0 ? (
                                employmentTypes.data.map((employmentType) => (
                                    <TableRow key={employmentType.id}>
                                        <TableCell className="font-medium">
                                            {employmentType.name}
                                        </TableCell>
                                        <TableCell>
                                            {employmentType.description || (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {employmentType.positions_count ??
                                                0}
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Can permission="employment-types.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    onClick={() =>
                                                        handleEdit(
                                                            employmentType,
                                                        )
                                                    }
                                                    title="Edit employment type"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            <Can permission="employment-types.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() =>
                                                        handleDelete(
                                                            employmentType,
                                                        )
                                                    }
                                                    title="Delete employment type"
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
                                        colSpan={4}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No employment types found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={employmentTypes} />

                {createOpen && (
                    <CreateEmploymentTypeDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                    />
                )}

                {editOpen && selectedEmploymentType && (
                    <EditEmploymentTypeDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        employmentType={selectedEmploymentType}
                    />
                )}

                {deleteOpen && selectedEmploymentType && (
                    <DeleteEmploymentTypeDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        employmentType={selectedEmploymentType}
                    />
                )}
            </div>
        </>
    );
}

EmploymentTypesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Employment Types',
            href: employmentTypesRoutes.index().url,
        },
    ],
};
