import { CalendarDays } from 'lucide-react';
import type { ReactNode } from 'react';

type DashboardHeaderProps = {
    eyebrow: string;
    title: string;
    description: string;
    date?: string;
    children?: ReactNode;
};

/**
 * Shared page opener for portal dashboards: a quiet brand rule, a personal
 * greeting, and the page's primary actions.
 */
export function DashboardHeader({
    eyebrow,
    title,
    description,
    date,
    children,
}: DashboardHeaderProps) {
    return (
        <header className="flex flex-col gap-6 border-b border-border/70 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div className="min-w-0 space-y-2.5">
                <div className="flex items-center gap-2.5">
                    <span
                        aria-hidden="true"
                        className="h-px w-6 bg-emerald-700/70 dark:bg-emerald-400/70"
                    />
                    <p className="text-[11px] font-semibold tracking-[0.18em] text-emerald-800 uppercase dark:text-emerald-400">
                        {eyebrow}
                    </p>
                </div>
                <h1 className="text-2xl font-semibold tracking-tight text-balance text-foreground sm:text-[1.75rem]">
                    {title}
                </h1>
                <p className="max-w-3xl text-sm leading-6 text-muted-foreground">
                    {description}
                </p>
            </div>

            <div className="flex shrink-0 flex-col gap-3 sm:items-end">
                {date && (
                    <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <CalendarDays aria-hidden="true" className="size-3.5" />
                        {date}
                    </p>
                )}
                {children && (
                    <div className="flex flex-wrap items-center gap-2">
                        {children}
                    </div>
                )}
            </div>
        </header>
    );
}
