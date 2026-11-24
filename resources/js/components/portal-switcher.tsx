import { Link, usePage } from '@inertiajs/react';
import { ArrowLeftRight, FileText, Inbox, ShieldCheck } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import type { PortalOption } from '@/types';

const portalIcons: Record<string, LucideIcon> = {
    employee: FileText,
    approvals: Inbox,
    'department-head': Inbox,
    admin: ShieldCheck,
};

/**
 * Lists the workspaces the signed-in account may enter and how many approvals
 * are waiting in each. It sits in the header so a multi-role account can see
 * pending work in another workspace without leaving the current one.
 */
export function PortalSwitcher() {
    const { portals, currentPortal } = usePage().props;
    const cleanup = useMobileNavigation();

    if (portals.length < 2) {
        return null;
    }

    const pendingElsewhere = portals.reduce((highest, portal) => {
        if (portal.key === currentPortal) {
            return highest;
        }

        return Math.max(highest, portal.pendingCount ?? 0);
    }, 0);

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative"
                    title="Switch portal"
                >
                    <ArrowLeftRight className="h-5 w-5" />
                    <span className="sr-only">Switch portal</span>
                    {pendingElsewhere > 0 && (
                        <span className="absolute top-1 right-1 flex size-4 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-white">
                            {pendingElsewhere > 9 ? '9+' : pendingElsewhere}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-72">
                <DropdownMenuLabel className="text-xs font-medium text-muted-foreground">
                    Switch portal
                </DropdownMenuLabel>
                <DropdownMenuGroup>
                    {portals.map((portal) => (
                        <PortalRow
                            key={portal.key}
                            portal={portal}
                            onNavigate={cleanup}
                        />
                    ))}
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function PortalRow({
    portal,
    onNavigate,
}: {
    portal: PortalOption;
    onNavigate: () => void;
}) {
    const Icon = portalIcons[portal.key] ?? FileText;
    const pending = portal.pendingCount ?? 0;

    if (portal.isCurrent) {
        return (
            <DropdownMenuItem disabled>
                <Icon className="mr-2 size-4" />
                <span className="flex-1">{portal.label}</span>
                <span className="text-xs text-muted-foreground">Current</span>
            </DropdownMenuItem>
        );
    }

    return (
        <DropdownMenuItem asChild>
            <Link
                className="block w-full cursor-pointer"
                href={portal.url}
                prefetch
                onClick={onNavigate}
            >
                <Icon className="mr-2 size-4" />
                <span className="flex-1">{portal.label}</span>
                {pending > 0 && (
                    <Badge variant="secondary">{pending} pending</Badge>
                )}
            </Link>
        </DropdownMenuItem>
    );
}
