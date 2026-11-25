import { Head, router, useForm } from '@inertiajs/react';
import { Copy, Pencil, Plus, Search, Trash2 } from 'lucide-react';
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
import workflowsRoutes from '@/routes/workflows';
import type { WorkflowSummary } from '@/types/workflow';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { CreateWorkflowDialog } from './create';
import { DeleteWorkflowDialog } from './delete';
import { DuplicateWorkflowDialog } from './duplicate';

type WorkflowsIndexProps = {
    workflows: PaginatedDataResponse<WorkflowSummary>;
    filters: { search?: string };
    canDelete: boolean;
};

export default function WorkflowsIndex({
    workflows,
    filters,
    canDelete,
}: WorkflowsIndexProps) {
    const { data, setData } = useForm({ search: filters.search ?? '' });

    const [createOpen, setCreateOpen] = useState(false);
    const [selected, setSelected] = useState<WorkflowSummary | null>(null);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [duplicateOpen, setDuplicateOpen] = useState(false);

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        router.get(
            workflowsRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Workflows" />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Workflow Management"
                    description="Define the approval chains travel orders follow."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="workflow.manage">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Workflow
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
                                <TableHead>Name</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Steps</TableHead>
                                <TableHead>Offices</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {workflows.data.length > 0 ? (
                                workflows.data.map((workflow) => (
                                    <TableRow key={workflow.id}>
                                        <TableCell className="font-medium">
                                            {workflow.name}
                                            {workflow.is_default && (
                                                <Badge
                                                    variant="secondary"
                                                    className="ml-2"
                                                >
                                                    Default
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {workflow.slug}
                                        </TableCell>
                                        <TableCell>
                                            {workflow.steps_count ?? 0}
                                        </TableCell>
                                        <TableCell>
                                            {workflow.offices_count ?? 0}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    workflow.is_active
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {workflow.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-blue-600 hover:bg-blue-50 hover:text-blue-700"
                                                onClick={() =>
                                                    router.visit(
                                                        workflowsRoutes.edit(
                                                            workflow,
                                                        ).url,
                                                    )
                                                }
                                                title="Edit workflow"
                                            >
                                                <Pencil />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-amber-600 hover:bg-amber-50 hover:text-amber-700"
                                                onClick={() => {
                                                    setSelected(workflow);
                                                    setDuplicateOpen(true);
                                                }}
                                                title="Duplicate workflow"
                                            >
                                                <Copy />
                                            </Button>
                                            {canDelete && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() => {
                                                        setSelected(workflow);
                                                        setDeleteOpen(true);
                                                    }}
                                                    title="Delete workflow"
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
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No workflows found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={workflows} />

                {createOpen && (
                    <CreateWorkflowDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                    />
                )}
                {duplicateOpen && selected && (
                    <DuplicateWorkflowDialog
                        isOpen={duplicateOpen}
                        onClose={() => setDuplicateOpen(false)}
                        workflow={selected}
                    />
                )}
                {deleteOpen && selected && (
                    <DeleteWorkflowDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        workflow={selected}
                    />
                )}
            </div>
        </>
    );
}

WorkflowsIndex.layout = {
    breadcrumbs: [{ title: 'Workflows', href: workflowsRoutes.index().url }],
};
