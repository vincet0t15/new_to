import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import type { PaginatedDataResponse } from '@/types/pagination';
import Pagination from '@/components/pagination';

vi.mock('@inertiajs/react', () => ({
    Link: ({
        href,
        children,
        preserveState: _preserveState,
        preserveScroll: _preserveScroll,
        ...props
    }: {
        href: string;
        children: ReactNode;
        preserveState?: boolean;
        preserveScroll?: boolean;
        [key: string]: unknown;
    }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

function page(overrides: Partial<PaginatedDataResponse<unknown>> = {}) {
    return {
        current_page: 2,
        data: [],
        first_page_url: '/items?page=1',
        from: 16,
        last_page: 3,
        last_page_url: '/items?page=3',
        links: [
            {
                url: '/items?page=1',
                label: '&laquo; Previous',
                active: false,
            },
            { url: '/items?page=1', label: '1', active: false },
            { url: null, label: '2', active: true },
            { url: '/items?page=3', label: '3', active: false },
            {
                url: '/items?page=3',
                label: 'Next &raquo;',
                active: false,
            },
        ],
        next_page_url: '/items?page=3',
        path: '/items',
        per_page: 15,
        prev_page_url: '/items?page=1',
        to: 30,
        total: 45,
        ...overrides,
    };
}

describe('shared pagination', () => {
    it('exposes a labelled nav with the current page marked', () => {
        render(<Pagination data={page()} />);

        expect(
            screen.getByRole('navigation', { name: 'Pagination' }),
        ).toBeTruthy();
        expect(
            screen.getByText(
                (_, element) =>
                    element?.tagName === 'P' &&
                    element.textContent?.replace(/\s+/g, ' ').trim() ===
                        'Showing 16 to 30 of 45 total',
            ),
        ).toBeTruthy();
        expect(
            screen.getByLabelText('Page 2').getAttribute('aria-current'),
        ).toBe('page');
        expect(screen.getByLabelText('Previous page').tagName).toBe('A');
        expect(screen.getByLabelText('Page 2').tagName).toBe('SPAN');
    });

    it('hides itself when there are no results', () => {
        const { container } = render(
            <Pagination data={page({ total: 0, links: [] })} />,
        );

        expect(container.textContent).toBe('');
    });
});
