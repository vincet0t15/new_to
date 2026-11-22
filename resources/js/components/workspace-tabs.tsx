import { Link, usePage } from '@inertiajs/react';
import { FileText, Inbox } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type Tab = {
    key: string;
    label: string;
    href: string;
    icon: LucideIcon;
    active: boolean;
};

/**
 * Switches between a person's own travel orders and the processing queue
 * without signing out. Shown only when the account actually has both, and
 * only while standing in one of those two workspaces.
 */
export function WorkspaceTabs() {
    const { portals, currentPortal } = usePage().props;

    const personal = portals.find((portal) => portal.key === 'employee');
    const processingPortals = portals.filter(
        (portal) => portal.inboxUrl !== null,
    );
    const processing =
        processingPortals.find((portal) => portal.key === currentPortal) ??
        processingPortals[0];

    if (
        personal === undefined ||
        processing === undefined ||
        processing.inboxUrl === null
    ) {
        return null;
    }

    const currentIsPersonal = currentPortal === personal.key;
    const currentIsProcessing = currentPortal === processing.key;

    if (!currentIsPersonal && !currentIsProcessing) {
        return null;
    }

    const tabs: Tab[] = [
        {
            key: 'personal',
            label: 'My Workspace',
            href: personal.url,
            icon: FileText,
            active: currentIsPersonal,
        },
        {
            key: 'processing',
            label: 'Processing Inbox',
            href: processing.inboxUrl,
            icon: Inbox,
            active: currentIsProcessing,
        },
    ];

    return (
        <nav
            aria-label="Workspace"
            className="flex gap-1 border-t border-sidebar-border/50 px-4 md:px-6"
        >
            {tabs.map((tab) => (
                <Link
                    key={tab.key}
                    href={tab.href}
                    prefetch
                    aria-current={tab.active ? 'page' : undefined}
                    className={cn(
                        'relative flex h-10 items-center gap-2 px-3 text-sm font-medium transition-colors',
                        'text-muted-foreground hover:text-foreground',
                        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset',
                        tab.active &&
                            'text-foreground after:absolute after:inset-x-2 after:bottom-0 after:h-0.5 after:bg-primary',
                    )}
                >
                    <tab.icon aria-hidden="true" className="size-4" />
                    {tab.label}
                </Link>
            ))}
        </nav>
    );
}
