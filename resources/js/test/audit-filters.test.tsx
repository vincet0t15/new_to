import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { TravelOrderAction } from '@/types/travel-order';
import AdminAuditIndex from '@/pages/admin/audit/index';

const inertia = vi.hoisted(() => ({
    router: {
        get: vi.fn(),
    },
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({
        href,
        children,
        ...props
    }: {
        href: string;
        children: ReactNode;
        [key: string]: unknown;
    }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    router: inertia.router,
}));

vi.mock('@/components/heading', () => ({
    default: ({
        title,
        description,
    }: {
        title: string;
        description: string;
    }) => (
        <div>
            <h1>{title}</h1>
            <p>{description}</p>
        </div>
    ),
}));

function emptyActions(): PaginatedDataResponse<TravelOrderAction> {
    return {
        current_page: 1,
        data: [],
        first_page_url: '/admin/audit?page=1',
        from: null,
        last_page: 1,
        last_page_url: '/admin/audit?page=1',
        links: [],
        next_page_url: null,
        path: '/admin/audit',
        per_page: 20,
        prev_page_url: null,
        to: null,
        total: 0,
    };
}

describe('admin audit filters', () => {
    beforeEach(() => {
        inertia.router.get.mockClear();
    });

    it('submits the visible filters to the audit endpoint', async () => {
        const user = userEvent.setup();

        render(
            <AdminAuditIndex
                actions={emptyActions()}
                events={['approved', 'returned']}
            />,
        );

        await user.type(screen.getByLabelText('Search'), 'Davao');
        await user.selectOptions(screen.getByLabelText('Event'), 'approved');
        await user.click(screen.getByRole('button', { name: 'Apply filters' }));

        expect(inertia.router.get).toHaveBeenCalledWith(
            '/admin/audit',
            {
                search: 'Davao',
                event: 'approved',
                date_from: undefined,
                date_to: undefined,
            },
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('clears every filter when reset is clicked', async () => {
        const user = userEvent.setup();

        render(
            <AdminAuditIndex
                actions={emptyActions()}
                filters={{
                    search: 'Davao',
                    event: 'approved',
                    date_from: '2026-01-01',
                    date_to: '2026-01-31',
                }}
                events={['approved']}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Reset' }));

        expect(inertia.router.get).toHaveBeenCalledWith(
            '/admin/audit',
            undefined,
            expect.objectContaining({ preserveState: true }),
        );
    });
});
