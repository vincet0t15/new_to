import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type DashboardPanelProps = {
    title: string;
    description?: string;
    icon?: LucideIcon;
    action?: ReactNode;
    footer?: ReactNode;
    children: ReactNode;
    className?: string;
    contentClassName?: string;
};

/**
 * A content panel with a consistent header, optional footer, and flush
 * content for tables or lists.
 */
export function DashboardPanel({
    title,
    description,
    icon: Icon,
    action,
    footer,
    children,
    className,
    contentClassName,
}: DashboardPanelProps) {
    return (
        <section
            className={cn(
                'overflow-hidden rounded-xl border border-border/80 bg-card text-card-foreground shadow-xs',
                className,
            )}
        >
            <header className="flex flex-wrap items-start justify-between gap-3 border-b border-border/70 px-5 py-4">
                <div className="flex min-w-0 items-start gap-3">
                    {Icon && (
                        <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border border-border/70 bg-muted/50 text-muted-foreground">
                            <Icon aria-hidden="true" className="size-4" />
                        </span>
                    )}
                    <div className="min-w-0">
                        <h2 className="text-sm font-semibold tracking-tight">
                            {title}
                        </h2>
                        {description && (
                            <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                </div>
                {action && (
                    <div className="flex shrink-0 items-center gap-2">
                        {action}
                    </div>
                )}
            </header>

            <div className={cn('p-5', contentClassName)}>{children}</div>

            {footer && (
                <footer className="border-t border-border/70 px-5 py-3">
                    {footer}
                </footer>
            )}
        </section>
    );
}
