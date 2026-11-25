import { Link } from '@inertiajs/react';
import type { PaginationLink, PaginatedDataResponse } from '@/types/pagination';

function labelText(label: string): string {
    if (label.includes('&laquo;')) {
        return '«';
    }

    if (label.includes('&raquo;')) {
        return '»';
    }

    return label.replaceAll('&amp;', '&');
}

function accessibleLabel(link: PaginationLink): string {
    if (link.label.includes('&laquo;')) {
        return 'Previous page';
    }

    if (link.label.includes('&raquo;')) {
        return 'Next page';
    }

    return `Page ${labelText(link.label)}`;
}

export default function Pagination({
    data,
}: {
    data: PaginatedDataResponse<unknown>;
}) {
    if (data.total === 0) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className="mt-4 flex flex-wrap items-center justify-between gap-2"
        >
            <p className="text-sm text-muted-foreground">
                Showing <strong>{data.from}</strong> to{' '}
                <strong>{data.to}</strong> of <strong>{data.total}</strong>{' '}
                total
            </p>

            {data.last_page > 1 && (
                <ul className="flex gap-1">
                    {data.links.map((link, index) => {
                        const text = labelText(link.label);

                        return (
                            <li key={`${link.label}-${index}`}>
                                {link.url === null ? (
                                    <span
                                        aria-current={
                                            link.active ? 'page' : undefined
                                        }
                                        aria-label={accessibleLabel(link)}
                                        className="block cursor-not-allowed rounded-sm border border-input px-3 py-1 text-sm opacity-50"
                                    >
                                        {text}
                                    </span>
                                ) : (
                                    <Link
                                        href={link.url}
                                        preserveState
                                        preserveScroll
                                        aria-current={
                                            link.active ? 'page' : undefined
                                        }
                                        aria-label={accessibleLabel(link)}
                                        className={`block rounded-sm border px-3 py-1 text-sm ${
                                            link.active
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-input text-foreground hover:bg-muted'
                                        }`}
                                    >
                                        {text}
                                    </Link>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </nav>
    );
}
