import { Link, usePage } from '@inertiajs/react';
import { Briefcase, Building2, LayoutGrid, UserCog, Users } from 'lucide-react';
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
import employees from '@/routes/employees';
import employmentTypes from '@/routes/employment-types';
import offices from '@/routes/offices';
import positions from '@/routes/positions';
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
