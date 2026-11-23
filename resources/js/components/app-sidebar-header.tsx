import { Link, usePage } from '@inertiajs/react';
import { Bell, Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { WorkspaceTabs } from '@/components/workspace-tabs';
import { useAppearance } from '@/hooks/use-appearance';
import { useInitials } from '@/hooks/use-initials';
import notificationRoutes from '@/routes/notifications';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth, notifications, unreadNotificationsCount } = usePage().props;
    const getInitials = useInitials();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const [mounted, setMounted] = useState(false);

    useEffect(() => setMounted(true), []);

    return (
        <header className="sticky top-0 z-40 flex shrink-0 flex-col border-b border-sidebar-border/50 bg-background transition-[width,height] ease-linear">
            <div className="flex h-16 items-center justify-between gap-2 px-6 group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
                <div className="flex items-center gap-2">
                    <SidebarTrigger className="-ml-1" />
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                <div className="flex items-center gap-1">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="relative"
                            >
                                <Bell className="h-5 w-5" />
                                <span className="sr-only">Notifications</span>
                                {unreadNotificationsCount > 0 && (
                                    <span className="absolute top-1 right-1 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                                        {unreadNotificationsCount > 9
                                            ? '9+'
                                            : unreadNotificationsCount}
                                    </span>
                                )}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-80">
                            <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            {notifications.length > 0 ? (
                                <div className="max-h-80 overflow-y-auto">
                                    {notifications.map((notification) => (
                                        <Link
                                            key={notification.id}
                                            href={
                                                notificationRoutes.open(
                                                    notification.id,
                                                ).url
                                            }
                                            className={`block px-4 py-3 text-sm hover:bg-muted/50 ${
                                                notification.read_at
                                                    ? ''
                                                    : 'font-semibold'
                                            }`}
                                        >
                                            <p>{notification.title}</p>
                                            <p className="text-xs font-normal text-muted-foreground">
                                                {notification.message}
                                            </p>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <div className="p-4 text-center text-sm text-muted-foreground">
                                    No notifications yet
                                </div>
                            )}
                            <DropdownMenuSeparator />
                            <Link
                                href={notificationRoutes.index().url}
                                className="block px-4 py-2 text-center text-sm font-medium text-primary hover:bg-muted/50"
                            >
                                Open inbox
                            </Link>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        }
                    >
                        {mounted && resolvedAppearance === 'dark' ? (
                            <Sun className="h-5 w-5" />
                        ) : (
                            <Moon className="h-5 w-5" />
                        )}
                        <span className="sr-only">Toggle theme</span>
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                className="size-10 rounded-full p-1"
                            >
                                <Avatar className="size-8 overflow-hidden rounded-full">
                                    <AvatarImage
                                        src={auth.user?.avatar}
                                        alt={auth.user?.name}
                                    />
                                    <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {getInitials(auth.user?.name ?? '')}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="w-56" align="end">
                            {auth.user && <UserMenuContent user={auth.user} />}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
            <WorkspaceTabs />
        </header>
    );
}
