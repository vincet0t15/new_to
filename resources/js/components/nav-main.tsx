import { Link, usePage } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useCan } from '@/hooks/use-can';
import type { NavGroup, NavItem } from '@/types';

export function NavMain({ groups }: { groups: NavGroup[] }) {
    const { isCurrentUrl } = useCurrentUrl();
    const can = useCan();
    const { auth } = usePage().props;
    const roles = auth?.roles ?? [];

    // Hide anything the signed-in user has no permission for, then drop the
    // section entirely when nothing is left so no orphan label is rendered.
    // An item may name one permission, an any-of set, a role, or a mix.
    const isVisible = (item: NavItem) =>
        (!item.permission || can(item.permission)) &&
        (!item.permissions?.length ||
            item.permissions.some((permission) => can(permission))) &&
        (!item.role || roles.includes(item.role));

    const visibleGroups = groups
        .map((group) => ({
            ...group,
            items: group.items.filter(isVisible),
        }))
        .filter((group) => group.items.length > 0);

    if (visibleGroups.length === 0) {
        return null;
    }

    return (
        <>
            {visibleGroups.map((group) => (
                <SidebarGroup key={group.label} className="px-2 py-0">
                    <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                    <SidebarMenu>
                        {group.items.map((item) => (
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
                                {typeof item.badge === 'number' &&
                                    item.badge > 0 && (
                                        <SidebarMenuBadge>
                                            {item.badge > 99
                                                ? '99+'
                                                : item.badge}
                                        </SidebarMenuBadge>
                                    )}
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
