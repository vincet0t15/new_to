import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { NotificationInboxItem } from '@/types/notification';
import NotificationsIndex from '@/pages/notifications/index';

const inertia = vi.hoisted(() => ({
    router: {
        post: vi.fn(),
    },
    props: {
        unreadNotificationsCount: 2,
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
    usePage: () => ({ props: inertia.props }),
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

function page(): PaginatedDataResponse<NotificationInboxItem> {
    return {
        current_page: 1,
        data: [],
        first_page_url: '/inbox?page=1',
        from: null,
        last_page: 1,
        last_page_url: '/inbox?page=1',
        links: [],
        next_page_url: null,
        path: '/inbox',
        per_page: 20,
        prev_page_url: null,
        to: null,
        total: 0,
    };
}

describe('notification mark all read', () => {
    beforeEach(() => {
        inertia.router.post.mockClear();
        inertia.props.unreadNotificationsCount = 2;
    });

    it('posts to the read-all endpoint', async () => {
        const user = userEvent.setup();

        render(<NotificationsIndex notificationItems={page()} />);
        await user.click(screen.getByRole('button', { name: 'Mark all read' }));

        expect(inertia.router.post).toHaveBeenCalledWith(
            '/inbox/read-all',
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('disables the action when nothing is unread', () => {
        inertia.props.unreadNotificationsCount = 0;

        render(<NotificationsIndex notificationItems={page()} />);

        expect(
            screen.getByRole('button', { name: 'Mark all read' }),
        ).toHaveProperty('disabled', true);
    });
});
