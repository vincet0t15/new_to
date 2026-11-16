import { Link } from '@inertiajs/react';
import { ArrowLeftRight, Check } from 'lucide-react';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import type { PortalOption } from '@/types';

type PortalSwitcherProps = {
    portals: PortalOption[];
};

/**
 * Lists the workspaces the signed-in account may enter.
 *
 * Switching is a plain navigation between route prefixes, so a multi-role
 * account never has to sign out and back in.
 */
export function PortalSwitcher({ portals }: PortalSwitcherProps) {
    const cleanup = useMobileNavigation();

    if (portals.length < 2) {
        return null;
    }

    return (
        <>
            <DropdownMenuSeparator />
            <DropdownMenuLabel className="text-xs font-medium text-muted-foreground">
                Switch portal
            </DropdownMenuLabel>
            <DropdownMenuGroup>
                {portals.map((portal) =>
                    portal.isCurrent ? (
                        <DropdownMenuItem key={portal.key} disabled>
                            <Check className="mr-2" />
                            <span className="flex-1">{portal.label}</span>
                            <span className="text-xs text-muted-foreground">
                                Current
                            </span>
                        </DropdownMenuItem>
                    ) : (
                        <DropdownMenuItem key={portal.key} asChild>
                            <Link
                                className="block w-full cursor-pointer"
                                href={portal.url}
                                prefetch
                                onClick={cleanup}
                            >
                                <ArrowLeftRight className="mr-2" />
                                <span className="flex-1">{portal.label}</span>
                            </Link>
                        </DropdownMenuItem>
                    ),
                )}
            </DropdownMenuGroup>
        </>
    );
}
