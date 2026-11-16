import { Link } from '@inertiajs/react';
import type { PaginatedDataResponse } from '@/types/pagination';

export default function Pagination({
    data,
}: {
    data: PaginatedDataResponse<unknown>;
}) {
    if (data.total === 0) {
        return null;
    }

    return (
        <div className="mt-4 flex items-center justify-between gap-2">
            <p className="text-sm text-muted-foreground">
                Showing <strong>{data.from}</strong> to{' '}
                <strong>{data.to}</strong> from a total of{' '}
                <strong>{data.total}</strong>
            </p>

            {data.last_page > 1 && (
                <div className="flex gap-1">
                    {data.links.map((link, index) => (
                        <Link
                            key={index}
                            href={link.url ?? '#'}
                            preserveState
                            preserveScroll
                            className={`rounded-sm border px-3 py-1 text-sm ${
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-input text-foreground hover:bg-muted'
                            } ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
