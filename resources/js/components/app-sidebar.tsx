import { Link, usePage } from '@inertiajs/react';
import {
    Briefcase,
    Building2,
    Car,
    FileText,
    GitBranch,
    Inbox,
    LayoutGrid,
    Plus,
    RotateCcw,
    ShieldCheck,
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
import departmentHead from '@/routes/department-head';
import designations from '@/routes/designations';
import employees from '@/routes/employees';
import employmentTypes from '@/routes/employment-types';
import offices from '@/routes/offices';
import positions from '@/routes/positions';
import roles from '@/routes/roles';
import travelOrders from '@/routes/travel-orders';
import transportationModes from '@/routes/transportation-modes';
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
 * The admin portal keeps the organization reference data beside its
 * dashboard; the other portals fill in as their Travel Order screens land.
 */
function adminGroups(dashboardUrl: string): NavGroup[] {
    return [
        overviewGroup(dashboardUrl, 'Dashboard'),
        {
            label: 'Transportation',
            items: [
                {
                    title: 'Transportation Modes',
                    href: transportationModes.index().url,
                    icon: Car,
                    permission: 'transportation-modes.view',
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
                    title: 'Employees',
                    href: employees.index().url,
                    icon: Users,
                    permission: 'administration.view',
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
            ],
        },
        {
            label: 'Access Control',
            items: [
                {
                    title: 'User Accounts',
                    href: accounts.index().url,
                    icon: UserCog,
                    permission: 'accounts.view',
                },
                {
                    title: 'Roles & Permissions',
                    href: roles.index().url,
                    icon: ShieldCheck,
                    permission: 'roles.view',
                },
            ],
        },
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
                    title: 'Register',
                    href: admin.travelOrders.index().url,
                    icon: FileText,
                    permission: 'travel-orders.view-all',
                },
                {
                    title: 'Reports',
                    href: admin.reports.index().url,
                    icon: FileText,
                    permission: 'travel-orders.view-all',
                },
                {
                    title: 'Audit Log',
                    href: admin.audit.index().url,
                    icon: ShieldCheck,
                    permission: 'audit.view',
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
                    icon: ShieldCheck,
                    permission: 'workflow.manage',
                },
                {
                    title: 'Designations',
                    href: designations.index().url,
                    icon: Users,
                    permission: 'designations.manage',
                },
                {
                    title: 'Route Preview',
                    href: workflows.preview().url,
                    icon: GitBranch,
                    permission: 'workflow.manage',
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
              ? [
                    overviewGroup(workspaceUrl, 'Dashboard'),
                    {
                        label: 'Travel Orders',
                        items: [
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
                            {
                                title: 'New Travel Order',
                                href: travelOrders.create().url,
                                icon: Plus,
                                permission: 'travel-orders.create',
                            },
                        ],
                    },
                ]
              : portal?.key === 'department-head'
                ? [
                      overviewGroup(workspaceUrl, 'Dashboard'),
                      {
                          label: 'Travel Orders',
                          items: [
                              {
                                  title: 'Approval Inbox',
                                  href: departmentHead.inbox().url,
                                  icon: Inbox,
                                  permission: 'travel-orders.view',
                              },
                          ],
                      },
                  ]
                : [
                      overviewGroup(
                          workspaceUrl,
                          portal?.key === 'approvals' ? 'Inbox' : 'Dashboard',
                      ),
                  ];

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
