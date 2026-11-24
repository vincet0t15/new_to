import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { TravelOrder } from '@/types/travel-order';
import { makeTravelOrder } from '@/test/fixtures';
import AdminTravelOrdersIndex from '@/pages/admin/travel-orders/index';

const inertia = vi.hoisted(() => ({
    router: {
        get: vi.fn(),
        post: vi.fn(),
        visit: vi.fn(),
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
    usePage: () => ({ props: { errors: {} } }),
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

function page(orders: TravelOrder[]): PaginatedDataResponse<TravelOrder> {
    return {
        current_page: 1,
        data: orders,
        first_page_url: '/admin/travel-orders?page=1',
        from: orders.length > 0 ? 1 : null,
        last_page: 1,
        last_page_url: '/admin/travel-orders?page=1',
        links: [],
        next_page_url: null,
        path: '/admin/travel-orders',
        per_page: 15,
        prev_page_url: null,
        to: orders.length > 0 ? orders.length : null,
        total: orders.length,
    };
}

describe('admin reassign dialog', () => {
    beforeEach(() => {
        inertia.router.post.mockClear();
    });

    it('posts the chosen holder without changing the step on the client', async () => {
        const user = userEvent.setup();
        const order = makeTravelOrder();

        render(
            <AdminTravelOrdersIndex
                travelOrders={page([order])}
                filters={{}}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Reassign' }));

        const dialog = screen.getByRole('dialog');
        await user.selectOptions(
            within(dialog).getByLabelText('New holder'),
            '8',
        );
        await user.type(
            within(dialog).getByLabelText('Remarks (optional)'),
            'Designation changed',
        );
        await user.click(
            within(dialog).getByRole('button', { name: 'Reassign' }),
        );

        expect(inertia.router.post).toHaveBeenCalledWith(
            '/admin/travel-orders/1/reassign',
            { to_user_id: 8, remarks: 'Designation changed' },
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('does not offer reassign when there is no candidate or the order is not submitted', () => {
        const order = makeTravelOrder({
            status: 'approved',
            reassign_candidates: [],
        });

        render(
            <AdminTravelOrdersIndex
                travelOrders={page([order])}
                filters={{}}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Reassign' }),
        ).not.toBeInTheDocument();
    });
});
