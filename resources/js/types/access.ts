export type RoleSummary = {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
};

export type RoleRecord = RoleSummary & {
    description: string | null;
    users_count: number;
    permissions_count: number;
    permissions: string[];
};

export type RoleOption = RoleSummary & {
    description: string | null;
    permissions_count: number;
    requires_admin_permission: boolean;
};

export type RoleFormData = {
    name: string;
    description: string;
    permissions: string[];
};

export type AccountEmployee = {
    id: number;
    full_name: string;
    office_name: string | null;
};

export type AccountRecord = {
    id: number;
    name: string;
    username: string;
    email: string | null;
    employee: AccountEmployee | null;
    roles: RoleSummary[];
    created_at: string | null;
    is_self: boolean;
};

export type EmployeeAccountOption = {
    id: number;
    full_name: string;
    office_name: string;
};

export type AccountFormData = {
    name: string;
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
    employee_id: string;
    roles: number[];
};

export type AccountRolesFormData = {
    roles: number[];
};

export type AccountEmployeeFormData = {
    employee_id: string;
};
