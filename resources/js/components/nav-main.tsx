import { Link, usePage } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useCan } from '@/hooks/use-can';
import type { NavItem } from '@/types';

export function NavMain({ items }: { items: NavItem[] }) {
    const { isCurrentUrl } = useCurrentUrl();
    const can = useCan();
    const { auth } = usePage().props;
    const roles = auth?.roles ?? [];

    // Hide anything the signed-in user has no permission for, then drop the
    // section entirely when nothing is left so no orphan label is rendered.
    // An item may name one permission, an any-of set, a role, or a mix.
    const visibleItems = items.filter(
        (item) =>
            (!item.permission || can(item.permission)) &&
            (!item.permissions?.length ||
                item.permissions.some((permission) => can(permission))) &&
            (!item.role || roles.includes(item.role)),
    );

    if (visibleItems.length === 0) {
        return null;
    }

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {visibleItems.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            tooltip={{ children: item.title }}
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
