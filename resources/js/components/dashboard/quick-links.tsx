import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type DashboardQuickLink = {
    title: string;
    description: string;
    href: string;
    icon: LucideIcon;
    badge?: string | number | null;
};

/**
 * A compact navigation list for the actions a portal user reaches most.
 */
export function QuickLinks({ links }: { links: DashboardQuickLink[] }) {
    return (
        <ul className="divide-y divide-border/70">
            {links.map((link) => {
                const Icon = link.icon;

                return (
                    <li key={link.href}>
                        <Link
                            href={link.href}
                            className="group flex items-start gap-3 px-5 py-4 transition-colors hover:bg-muted/40 focus-visible:bg-muted/40 focus-visible:outline-none"
                        >
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg border border-border/70 bg-background text-muted-foreground transition-colors group-hover:border-emerald-700/20 group-hover:bg-emerald-50 group-hover:text-emerald-800 dark:group-hover:border-emerald-400/20 dark:group-hover:bg-emerald-950/40 dark:group-hover:text-emerald-300">
                                <Icon aria-hidden="true" className="size-4" />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="flex items-center gap-2">
                                    <span className="text-sm font-medium text-foreground">
                                        {link.title}
                                    </span>
                                    {link.badge !== undefined &&
                                        link.badge !== null &&
                                        link.badge !== 0 && (
                                            <Badge
                                                variant="secondary"
                                                className={cn(
                                                    'h-5 px-1.5 text-[11px] tabular-nums',
                                                )}
                                            >
                                                {link.badge}
                                            </Badge>
                                        )}
                                </span>
                                <span className="mt-1 block text-xs leading-5 text-muted-foreground">
                                    {link.description}
                                </span>
                            </span>
                            <ArrowUpRight
                                aria-hidden="true"
                                className="mt-1 size-4 shrink-0 text-muted-foreground/60 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-foreground"
                            />
                        </Link>
                    </li>
                );
            })}
        </ul>
    );
}
