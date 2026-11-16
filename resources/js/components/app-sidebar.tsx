import { Link } from '@inertiajs/react';
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

const navGroups: NavGroup[] = [
    {
        label: 'Overview',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        // Reference data lives with the screens that own it. These are the
        // records every travel order draws from, so they stay one click from
        // anywhere in the admin portal.
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

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={navGroups} />
            </SidebarContent>
        </Sidebar>
    );
}
