import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    itinerarySignatories,
    letterhead,
    makeAbilities,
    makeTravelOrder,
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

function renderApproval(approve: boolean) {
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
