import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type EmptyStateProps = {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: ReactNode;
    className?: string;
};

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    className,
}: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-1.5 px-6 py-12 text-center',
                className,
            )}
        >
            <span className="mb-1 flex size-10 items-center justify-center rounded-full border border-border/70 bg-muted/50 text-muted-foreground">
                <Icon aria-hidden="true" className="size-4" />
            </span>
            <p className="text-sm font-medium text-foreground">{title}</p>
            <p className="max-w-sm text-xs leading-5 text-muted-foreground">
                {description}
            </p>
            {action && <div className="mt-3">{action}</div>}
        </div>
    );
}
