import { Head, router, useForm } from '@inertiajs/react';
import { Link2, Plus, Search, ShieldCheck } from 'lucide-react';
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
import accounts from '@/routes/accounts';
import type {
    AccountRecord,
    EmployeeAccountOption,
    RoleOption,
} from '@/types/access';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { CreateAccountDialog } from './create';
import { EditAccountRolesDialog } from './edit-roles';
import { LinkEmployeeDialog } from './link-employee';

type AccountsIndexProps = {
    accounts: PaginatedDataResponse<AccountRecord>;
    roles: RoleOption[];
    employees: EmployeeAccountOption[];
    filters: {
        search?: string;
    };
};

export default function AccountsIndex({
    accounts: accountPage,
    roles,
    employees,
    filters,
}: AccountsIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selectedAccount, setSelectedAccount] =
        useState<AccountRecord | null>(null);
    const [rolesOpen, setRolesOpen] = useState(false);
    const [linkAccount, setLinkAccount] = useState<AccountRecord | null>(null);
    const [linkOpen, setLinkOpen] = useState(false);

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            accounts.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Accounts" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="User Accounts"
                    description="Create logins and give each account the roles that decide what it can do."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="accounts.edit">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Account
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search name, username, email..."
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
                                <TableHead>Account</TableHead>
                                <TableHead>Employee</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {accountPage.data.length > 0 ? (
                                accountPage.data.map((account) => (
                                    <TableRow key={account.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">
                                                    {account.name}
                                                </span>
                                                {account.is_self && (
                                                    <Badge variant="outline">
                                                        You
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                @{account.username}
                                                {account.email
                                                    ? ` · ${account.email}`
                                                    : ''}
                                            </p>
                                        </TableCell>
                                        <TableCell>
                                            {account.employee ? (
                                                <>
                                                    <p>
                                                        {
                                                            account.employee
                                                                .full_name
                                                        }
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {
                                                            account.employee
                                                                .office_name
                                                        }
                                                    </p>
                                                </>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Not linked
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex max-w-[320px] flex-wrap gap-1">
                                                {account.roles.length > 0 ? (
                                                    account.roles.map(
                                                        (role) => (
                                                            <Badge
                                                                key={role.id}
                                                                variant={
                                                                    role.is_system
                                                                        ? 'secondary'
                                                                        : 'outline'
                                                                }
                                                            >
                                                                {role.name}
                                                            </Badge>
                                                        ),
                                                    )
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        No roles
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {account.created_at ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex flex-wrap justify-end gap-1">
                                                <Can permission="accounts.edit">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-green-600 hover:bg-green-50 hover:text-green-700 disabled:opacity-40"
                                                        onClick={() => {
                                                            setSelectedAccount(
                                                                account,
                                                            );
                                                            setRolesOpen(true);
                                                        }}
                                                        disabled={
                                                            account.is_self
                                                        }
                                                        title={
                                                            account.is_self
                                                                ? 'You cannot change your own roles'
                                                                : 'Edit roles'
                                                        }
                                                    >
                                                        <ShieldCheck />
                                                        Roles
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-blue-600 hover:bg-blue-50 hover:text-blue-700"
                                                        onClick={() => {
                                                            setLinkAccount(
                                                                account,
                                                            );
                                                            setLinkOpen(true);
                                                        }}
                                                        title={
                                                            account.employee
                                                                ? 'Change or remove employee link'
                                                                : 'Link employee'
                                                        }
                                                    >
                                                        <Link2 />
                                                        Employee
                                                    </Button>
                                                </Can>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No accounts found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={accountPage} />

                {createOpen && (
                    <CreateAccountDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                        roles={roles}
                        employees={employees}
                    />
                )}

                {rolesOpen && selectedAccount && (
                    <EditAccountRolesDialog
                        isOpen={rolesOpen}
                        onClose={() => setRolesOpen(false)}
                        account={selectedAccount}
                        roles={roles}
                    />
                )}

                {linkOpen && linkAccount && (
                    <LinkEmployeeDialog
                        key={linkAccount.id}
                        isOpen={linkOpen}
                        onClose={() => setLinkOpen(false)}
                        account={linkAccount}
                        employees={employees}
                    />
                )}
            </div>
        </>
    );
}

AccountsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Accounts',
            href: accounts.index().url,
        },
    ],
};
