import type { TravelOrderRevision } from '@/types/travel-order';

const changeStyles: Record<string, string> = {
    minor: 'text-slate-600 dark:text-slate-300',
    material: 'text-amber-600 dark:text-amber-300',
};

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export function RevisionTimeline({
    revisions,
}: {
    revisions: TravelOrderRevision[];
}) {
    if (!revisions || revisions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">No revisions yet.</p>
        );
    }

    return (
        <ol className="space-y-4">
            {revisions.map((revision) => (
                <li
                    key={revision.revision_number}
                    className="rounded-lg border p-3"
                >
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <p className="text-sm font-medium">
                            Revision {revision.revision_number}
                            <span
                                className={`ml-2 text-xs ${changeStyles[revision.change_type] ?? ''}`}
                            >
                                {revision.change_type === 'material'
                                    ? 'Material'
                                    : 'Minor'}
                            </span>
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {formatDate(revision.returned_at)}
                        </p>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Returned by {revision.returned_by ?? '—'}
                        {revision.returned_from_step
                            ? ` at ${revision.returned_from_step}`
                            : ''}
                    </p>
                    {revision.return_remarks && (
                        <p className="mt-2 text-sm whitespace-pre-wrap">
                            <span className="font-medium">Return remarks:</span>{' '}
                            {revision.return_remarks}
                        </p>
                    )}
                    {revision.changed_fields &&
                        revision.changed_fields.length > 0 && (
                            <p className="mt-2 text-xs text-muted-foreground">
                                Changed: {revision.changed_fields.join(', ')}
                            </p>
                        )}
                    {revision.revision_remarks && (
                        <p className="mt-2 text-sm whitespace-pre-wrap">
                            <span className="font-medium">
                                Revision remarks:
                            </span>{' '}
                            {revision.revision_remarks}
                        </p>
                    )}
                    <p className="mt-2 text-xs text-muted-foreground">
                        {revision.resubmitted_at
                            ? `Resubmitted by ${revision.revised_by ?? '—'} on ${formatDate(revision.resubmitted_at)}`
                            : 'Not yet resubmitted'}
                    </p>
                </li>
            ))}
        </ol>
    );
}
