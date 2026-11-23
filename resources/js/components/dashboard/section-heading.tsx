import type { ReactNode } from 'react';

type SectionHeadingProps = {
    id: string;
    title: string;
    description?: string;
    action?: ReactNode;
};

/**
 * Labels a dashboard group (travel orders, fuel operations) without
 * competing with the page title.
 */
export function SectionHeading({
    id,
    title,
    description,
    action,
}: SectionHeadingProps) {
    return (
        <div className="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2
                    id={id}
                    className="text-sm font-semibold tracking-tight text-foreground"
                >
                    {title}
                </h2>
                {description && (
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {action && <div className="shrink-0">{action}</div>}
        </div>
    );
}
