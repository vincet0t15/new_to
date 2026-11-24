import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { AnchorHTMLAttributes } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { PortalSwitcher } from '@/components/portal-switcher';
import type { PortalOption } from '@/types/portal';

const state = vi.hoisted(() => ({
    props: {
        portals: [] as PortalOption[],
        currentPortal: 'employee',
    },
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({
        href,
        children,
        prefetch: _prefetch,
        ...props
    }: AnchorHTMLAttributes<HTMLAnchorElement> & { prefetch?: boolean }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    usePage: () => ({ props: state.props }),
    router: {
        get: vi.fn(),
        post: vi.fn(),
        visit: vi.fn(),
    },
}));

function portal(
    key: string,
    label: string,
    pendingCount: number | null,
    isCurrent = false,
): PortalOption {
    return {
        key,
        label,
        url: `/${key}`,
        inboxUrl: key === 'employee' ? null : '/approvals',
        pendingCount,
        isCurrent,
    };
}

describe('PortalSwitcher', () => {
    beforeEach(() => {
        state.props.portals = [];
        state.props.currentPortal = 'employee';
    });

    it('is hidden for an account with only one portal', () => {
        state.props.portals = [portal('employee', 'Employee', 2, true)];

        render(<PortalSwitcher />);

        expect(screen.queryByTitle('Switch portal')).not.toBeInTheDocument();
    });

    it('shows pending work waiting in the other portals only', async () => {
        const user = userEvent.setup();
        state.props.portals = [
            portal('employee', 'Employee', 9, true),
            portal('approvals', 'Approval Inbox', 4),
            portal('admin', 'Admin', null),
        ];

        render(<PortalSwitcher />);

        const trigger = screen.getByTitle('Switch portal');
        expect(trigger).toHaveTextContent('4');

        await user.click(trigger);

        const approvalsLink = await screen.findByRole('menuitem', {
            name: /Approval Inbox/,
        });
        expect(approvalsLink).toHaveAttribute('href', '/approvals');
        expect(approvalsLink).toHaveTextContent('4 pending');
        expect(screen.getByText('Current')).toBeInTheDocument();
        expect(screen.queryByText('9 pending')).not.toBeInTheDocument();
        expect(screen.queryByText('0 pending')).not.toBeInTheDocument();
    });
});
