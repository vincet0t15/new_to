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
import roles from '@/routes/roles';
import type { RoleRecord } from '@/types/access';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { CreateRoleDialog } from './create';
import { DeleteRoleDialog } from './delete';
import { EditRoleDialog } from './edit';

type RolesIndexProps = {
    roles: PaginatedDataResponse<RoleRecord>;
    permissions: string[];
    filters: {
        search?: string;
    };
};

export default function RolesIndex({
    roles: rolePage,
    permissions,
    filters,
}: RolesIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selectedRole, setSelectedRole] = useState<RoleRecord | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleEdit = (role: RoleRecord) => {
        setSelectedRole(role);
        setEditOpen(true);
    };

    const handleDelete = (role: RoleRecord) => {
        setSelectedRole(role);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            roles.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Roles" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Roles & Permissions"
                    description="Create roles, choose what each one may do, then give those roles to accounts."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="roles.create">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Role
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search roles..."
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
                                <TableHead>Role</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Permissions</TableHead>
                                <TableHead>Accounts</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rolePage.data.length > 0 ? (
                                rolePage.data.map((role) => (
                                    <TableRow key={role.id}>
                                        <TableCell>
                                            <p className="font-medium">
                                                {role.name}
                                            </p>
                                            {role.description && (
                                                <p className="max-w-[320px] truncate text-xs text-muted-foreground">
                                                    {role.description}
                                                </p>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-mono text-xs text-muted-foreground">
                                            {role.slug}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {role.permissions_count}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {role.users_count}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    role.is_system
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {role.is_system
                                                    ? 'Built-in'
                                                    : 'Custom'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Can permission="roles.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    onClick={() =>
                                                        handleEdit(role)
                                                    }
                                                    title="Edit role"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            {!role.is_system && (
                                                <Can permission="roles.delete">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700 disabled:opacity-40"
                                                        onClick={() =>
                                                            handleDelete(role)
                                                        }
                                                        disabled={
                                                            role.users_count > 0
                                                        }
                                                        title={
                                                            role.users_count > 0
                                                                ? 'Reassign the accounts holding this role first'
                                                                : 'Delete role'
                                                        }
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </Can>
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
                                        No roles found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={rolePage} />

                {createOpen && (
                    <CreateRoleDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                        permissions={permissions}
                    />
                )}

                {editOpen && selectedRole && (
                    <EditRoleDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        role={selectedRole}
                        permissions={permissions}
                    />
                )}

                {deleteOpen && selectedRole && (
                    <DeleteRoleDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        role={selectedRole}
                    />
                )}
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: roles.index().url,
        },
    ],
};
