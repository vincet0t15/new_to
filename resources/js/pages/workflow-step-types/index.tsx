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
import workflowStepTypesRoutes from '@/routes/workflow-step-types';
import type { WorkflowStepType } from '@/types/workflow';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { CreateStepTypeDialog } from './create';
import { DeleteStepTypeDialog } from './delete';
import { EditStepTypeDialog } from './edit';

type Props = {
    stepTypes: PaginatedDataResponse<WorkflowStepType>;
    filters: { search?: string };
    canDelete: boolean;
};

export default function StepTypesIndex({
    stepTypes,
    filters,
    canDelete,
}: Props) {
    const { data, setData } = useForm({ search: filters.search ?? '' });

    const [createOpen, setCreateOpen] = useState(false);
    const [selected, setSelected] = useState<WorkflowStepType | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        router.get(
            workflowStepTypesRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Step Types" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Step Type Management"
                    description="Reusable definitions of what a step means."
                />
                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="workflow.manage">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Step Type
                        </Button>
                    </Can>
                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search step types..."
                            className="w-full pl-8"
                            value={data.search}
                            onChange={(e) => setData('search', e.target.value)}
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
                                <TableHead>Slug</TableHead>
                                <TableHead>Signature Title</TableHead>
                                <TableHead>Signatory</TableHead>
                                <TableHead>Usage</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {stepTypes.data.length > 0 ? (
                                stepTypes.data.map((type) => (
                                    <TableRow key={type.id}>
                                        <TableCell className="font-medium">
                                            {type.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {type.slug}
                                        </TableCell>
                                        <TableCell>
                                            {type.signature_title ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {type.is_signatory
                                                ? 'Yes'
                                                : 'Receipt'}
                                        </TableCell>
                                        <TableCell>
                                            {(type.steps_count ?? 0) +
                                                (type.designations_count ?? 0)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    type.is_active
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {type.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-blue-600"
                                                onClick={() => {
                                                    setSelected(type);
                                                    setEditOpen(true);
                                                }}
                                                title="Edit"
                                            >
                                                <Pencil />
                                            </Button>
                                            {canDelete && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600"
                                                    onClick={() => {
                                                        setSelected(type);
                                                        setDeleteOpen(true);
                                                    }}
                                                    title="Delete"
                                                >
                                                    <Trash2 />
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No step types found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={stepTypes} />

                {createOpen && (
                    <CreateStepTypeDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                    />
                )}
                {editOpen && selected && (
                    <EditStepTypeDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        stepType={selected}
                    />
                )}
                {deleteOpen && selected && (
                    <DeleteStepTypeDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        stepType={selected}
                    />
                )}
            </div>
        </>
    );
}

StepTypesIndex.layout = {
    breadcrumbs: [
        { title: 'Step Types', href: workflowStepTypesRoutes.index().url },
    ],
};
