import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    itinerarySignatories,
    letterhead,
    makeAbilities,
    makeTravelOrder,
    makeTripTicket,
    signatories,
} from '@/test/fixtures';
import ShowApproval from '@/pages/approvals/show';

const state = vi.hoisted(() => ({
    form: {
        data: {
            remarks: '',
            to_user_id: '',
            control_no: '',
            to_number: '',
            distributed: false,
            fund_source_id: '',
            earmark_number: '',
        },
        errors: {},
        processing: false,
        post: vi.fn(),
        reset: vi.fn(),
        setData: vi.fn(),
    },
    router: {
        post: vi.fn(),
        visit: vi.fn(),
    },
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    router: state.router,
    useForm: () => state.form,
}));

vi.mock('@/components/heading', () => ({
    default: ({ title }: { title: string }) => <h1>{title}</h1>,
}));

vi.mock('@/components/travel-orders/print/travel-order-document', () => ({
    TravelOrderDocument: ({
        signatories: lines,
    }: {
        signatories: typeof signatories;
    }) => (
        <div data-testid="order-doc">{lines.department_head_approval.name}</div>
    ),
}));

vi.mock('@/components/travel-orders/print/itinerary-document', () => ({
    ItineraryDocument: ({
        itinerarySignatories: lines,
    }: {
        itinerarySignatories: typeof itinerarySignatories;
    }) => <div data-testid="itinerary-doc">{lines.certified.name}</div>,
}));

vi.mock('@/components/travel-orders/print/trip-ticket-document', () => ({
    TripTicketDocument: ({
        tripTicket,
    }: {
        tripTicket: ReturnType<typeof makeTripTicket>;
    }) => (
        <div data-testid="trip-ticket-doc" data-ticket-id={tripTicket.id}>
            {tripTicket.ticket_no}
        </div>
    ),
}));

vi.mock('@/components/travel-orders/action-timeline', () => ({
    ActionTimeline: () => <div data-testid="timeline" />,
}));

vi.mock('@/components/travel-orders/revision-timeline', () => ({
    RevisionTimeline: () => <div data-testid="revisions" />,
}));

const urls = {
    inbox: '/approvals',
    receive: '/approvals/1/receive',
    approve: '/approvals/1/approve',
    acknowledge: '/approvals/1/acknowledge',
    return: '/approvals/1/return',
    forward: '/approvals/1/forward',
    earmark: '/approvals/1/earmark',
    assignFundSource: '/approvals/1/fund-source',
    record: '/approvals/1/record',
};

function renderApproval(
    approve: boolean,
    tripTickets: ReturnType<typeof makeTripTicket>[] = [],
) {
    return render(
        <ShowApproval
            travelOrder={makeTravelOrder()}
            route={[]}
            timeline={[]}
            revisions={[]}
            currentStep={null}
            myDeliveryStatus={null}
            forwardCandidates={[]}
            fundSources={[]}
            letterhead={letterhead}
            signatories={signatories}
            itinerarySignatories={itinerarySignatories}
            tripTickets={tripTickets}
            can={makeAbilities({ approve })}
            urls={urls}
        />,
    );
}

describe('approval document preview and actions', () => {
    beforeEach(() => {
        state.form.post.mockClear();
        state.form.reset.mockClear();
        state.router.visit.mockClear();
    });

    it('switches the shared document preview to the itinerary', async () => {
        const user = userEvent.setup();
        renderApproval(false);

        expect(
            screen.getByRole('tab', { name: 'Travel Order' }),
        ).toHaveAttribute('aria-selected', 'true');
        expect(screen.getByTestId('order-doc')).toHaveTextContent(
            'Pedro Bautista',
        );

        await user.click(screen.getByRole('tab', { name: 'Itinerary' }));

        expect(screen.getByRole('tab', { name: 'Itinerary' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(screen.getByTestId('itinerary-doc')).toHaveTextContent(
            'Maria Clara Santos',
        );
        expect(screen.queryByTestId('order-doc')).not.toBeInTheDocument();
    });

    it('switches the shared document preview to the printed trip ticket', async () => {
        const user = userEvent.setup();
        renderApproval(false, [makeTripTicket({ ticket_no: 'TT-2026-00007' })]);

        await user.click(screen.getByRole('tab', { name: 'Trip Ticket' }));

        expect(
            screen.getByRole('tab', { name: 'Trip Ticket' }),
        ).toHaveAttribute('aria-selected', 'true');
        expect(screen.getByTestId('trip-ticket-doc')).toHaveTextContent(
            'TT-2026-00007',
        );
        expect(screen.queryByTestId('itinerary-doc')).not.toBeInTheDocument();
    });

    it('explains when the travel order has no trip ticket yet', async () => {
        const user = userEvent.setup();
        renderApproval(false);

        await user.click(screen.getByRole('tab', { name: 'Trip Ticket' }));

        expect(
            screen.getByText(
                'No trip ticket has been issued for this travel order yet.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByTestId('trip-ticket-doc')).not.toBeInTheDocument();
    });

    it('moves between document tabs with the arrow keys', async () => {
        const user = userEvent.setup();
        renderApproval(false, [makeTripTicket()]);

        screen.getByRole('tab', { name: 'Travel Order' }).focus();
        await user.keyboard('{ArrowRight}');

        expect(screen.getByRole('tab', { name: 'Itinerary' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
    });

    it('posts an approval to the approval action URL', async () => {
        const user = userEvent.setup();
        renderApproval(true);

        await user.click(screen.getByRole('button', { name: 'Approve' }));

        expect(state.form.post).toHaveBeenCalledWith(
            urls.approve,
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('explains that there is nothing to do when no action is allowed', () => {
        renderApproval(false);

        expect(
            screen.getByText(
                'This travel order is not waiting for your action.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Approve' }),
        ).not.toBeInTheDocument();
    });
});
