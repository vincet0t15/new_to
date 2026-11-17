import { Link, usePage } from '@inertiajs/react';
import {
    BadgeCheck,
    Briefcase,
    Building2,
    Car,
    ChartColumn,
    ClipboardList,
    FileText,
    GitBranch,
    History,
    Inbox,
    KeyRound,
    Landmark,
    Layers,
    LayoutGrid,
    ListChecks,
    MapPin,
    Plus,
    Receipt,
    RotateCcw,
    Route,
    ShieldCheck,
    Tag,
    UserCog,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import accounts from '@/routes/accounts';
import admin from '@/routes/admin';
import approvals from '@/routes/approvals';
import departmentHead from '@/routes/department-head';
import designations from '@/routes/designations';
import employees from '@/routes/employees';
import employmentTypes from '@/routes/employment-types';
import expenseTypes from '@/routes/expense-types';
import fundSources from '@/routes/fund-sources';
import locations from '@/routes/locations';
import offices from '@/routes/offices';
import perDiemConfigurations from '@/routes/per-diem-configurations';
import positions from '@/routes/positions';
import roles from '@/routes/roles';
import travelOrders from '@/routes/travel-orders';
import transportationModes from '@/routes/transportation-modes';
import travelClassifications from '@/routes/travel-classifications';
import workflowStepTypes from '@/routes/workflow-step-types';
import workflows from '@/routes/workflows';
import type { NavGroup } from '@/types';

/**
 * The landing item every portal shares.
 */
function overviewGroup(href: string, title: string): NavGroup {
    return {
        label: 'Overview',
        items: [
            {
                title,
                href,
                icon: LayoutGrid,
            },
        ],
    };
}

/**
 * The employee workspace: file an order, follow it, and pick up anything that
 * came back for revision.
 */
function employeeGroups(dashboardUrl: string): NavGroup[] {
    return [
        overviewGroup(dashboardUrl, 'Dashboard'),
        {
            label: 'Travel Orders',
            items: [
                {
                    title: 'New Travel Order',
                    href: travelOrders.create().url,
                    icon: Plus,
                    permission: 'travel-orders.create',
                },
                {
                    title: 'My Travel Orders',
                    href: travelOrders.index().url,
                    icon: FileText,
                    permission: 'travel-orders.view',
                },
                {
                    title: 'Returned for Revision',
                    href: travelOrders.returned().url,
                    icon: RotateCcw,
                    permission: 'travel-orders.view',
                },
            ],
        },
    ];
}

/**
 * The department head workspace: the same approval inbox as the approvals
 * portal, reached from the department's own landing page.
 */
function departmentHeadGroups(dashboardUrl: string): NavGroup[] {
    return [
        overviewGroup(dashboardUrl, 'Dashboard'),
        {
            label: 'Approvals',
            items: [
                {
                    title: 'Approval Inbox',
                    href: departmentHead.inbox().url,
                    icon: Inbox,
                    permission: 'travel-orders.view',
                },
            ],
        },
    ];
}

/**
 * The approvals workspace: budget, mayor, vice mayor, and HRMO land straight
 * in the shared inbox, so the inbox itself is the portal's only section.
 */
function approvalsGroups(inboxUrl: string): NavGroup[] {
    return [
        {
            label: 'Approvals',
            items: [
                {
                    title: 'Approval Inbox',
                    href: inboxUrl,
                    icon: Inbox,
                    permission: 'travel-orders.view',
                },
            ],
        },
    ];
}

/**
 * The admin workspace, grouped by the job the Administrator is doing:
 * filing and reviewing orders, configuring the route, maintaining the travel
 * reference data, keeping the organization directory, and running accounts.
 */
function adminGroups(dashboardUrl: string): NavGroup[] {
    return [
        overviewGroup(dashboardUrl, 'Dashboard'),
        {
            label: 'Travel Orders',
            items: [
                {
                    title: 'New Travel Order',
                    href: travelOrders.create().url,
                    icon: Plus,
                    permission: 'travel-orders.create',
                },
                {
                    title: 'Travel Order Register',
                    href: admin.travelOrders.index().url,
                    icon: ClipboardList,
                    permission: 'travel-orders.view-all',
                },
                {
                    title: 'Reports',
                    href: admin.reports.index().url,
                    icon: ChartColumn,
                    permission: 'travel-orders.view-all',
                },
            ],
        },
        {
            label: 'Workflow',
            items: [
                {
                    title: 'Workflows',
                    href: workflows.index().url,
                    icon: GitBranch,
                    permission: 'workflow.manage',
                },
                {
                    title: 'Step Types',
                    href: workflowStepTypes.index().url,
                    icon: ListChecks,
                    permission: 'workflow.manage',
                },
                {
                    title: 'Designations',
                    href: designations.index().url,
                    icon: BadgeCheck,
                    permission: 'designations.manage',
                },
                {
                    title: 'Route Preview',
                    href: workflows.preview().url,
                    icon: Route,
                    permission: 'workflow.manage',
                },
            ],
        },
        {
            label: 'Travel Setup',
            items: [
                {
                    title: 'Transportation Modes',
                    href: transportationModes.index().url,
                    icon: Car,
                    permission: 'transportation-modes.view',
                },
                {
                    title: 'Expenses Authorized',
                    href: expenseTypes.index().url,
                    icon: Receipt,
                    permission: 'expense-types.view',
                },
                {
                    title: 'Fund Sources',
                    href: fundSources.index().url,
                    icon: Landmark,
                    permission: 'fund-sources.view',
                },
                {
                    title: 'Travel Classifications',
                    href: travelClassifications.index().url,
                    icon: Tag,
                    permission: 'travel-classifications.view',
                },
                {
                    title: 'Per Diem Configurations',
                    href: perDiemConfigurations.index().url,
                    icon: Layers,
                    permission: 'per-diem-configurations.view',
                },
                {
                    title: 'Locations',
                    href: locations.index().url,
                    icon: MapPin,
                    permission: 'locations.view',
                },
            ],
        },
        {
            label: 'Organization',
            items: [
                {
                    title: 'Offices',
                    href: offices.index().url,
                    icon: Building2,
                    permission: 'offices.view',
                },
                {
                    title: 'Positions',
                    href: positions.index().url,
                    icon: UserCog,
                    permission: 'positions.view',
                },
                {
                    title: 'Employment Types',
                    href: employmentTypes.index().url,
                    icon: Briefcase,
                    permission: 'employment-types.view',
                },
                {
                    title: 'Employees',
                    href: employees.index().url,
                    icon: Users,
                    permission: 'employees.view',
                },
            ],
        },
        {
            label: 'Administration',
            items: [
                {
                    title: 'User Accounts',
                    href: accounts.index().url,
                    icon: KeyRound,
                    permission: 'accounts.view',
                },
                {
                    title: 'Roles & Permissions',
                    href: roles.index().url,
                    icon: ShieldCheck,
                    permission: 'roles.view',
                },
                {
                    title: 'Audit Log',
                    href: admin.audit.index().url,
                    icon: History,
                    permission: 'audit.view',
                },
            ],
        },
    ];
}

export function AppSidebar() {
    const { portals, currentPortal } = usePage().props;
    const portal =
        portals.find((option) => option.key === currentPortal) ?? portals[0];

    const workspaceUrl = portal?.url ?? dashboard();

    const groups: NavGroup[] =
        portal?.key === 'admin'
            ? adminGroups(workspaceUrl)
            : portal?.key === 'employee'
              ? employeeGroups(workspaceUrl)
              : portal?.key === 'department-head'
                ? departmentHeadGroups(workspaceUrl)
                : portal?.key === 'approvals'
                  ? approvalsGroups(approvals.inbox().url)
                  : [overviewGroup(workspaceUrl, 'Dashboard')];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={workspaceUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={groups} />
            </SidebarContent>
        </Sidebar>
    );
}
