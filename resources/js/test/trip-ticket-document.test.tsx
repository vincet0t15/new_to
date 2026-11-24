import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { TripTicketDocument } from '@/components/travel-orders/print/trip-ticket-document';
import { letterhead, makeTravelOrder, makeTripTicket } from '@/test/fixtures';

describe('TripTicketDocument', () => {
    it('uses the configured letterhead and keeps each ticket on its own sheet', () => {
        const { container } = render(
            <TripTicketDocument
                travelOrder={makeTravelOrder()}
                tripTicket={makeTripTicket()}
                letterhead={{
                    ...letterhead,
                    republic: 'Republika ng Pilipinas',
                }}
            />,
        );

        expect(screen.getByText('Republika ng Pilipinas')).toBeInTheDocument();
        expect(screen.getByText('Palawan')).toBeInTheDocument();
        expect(screen.getByAltText('Bagong Pilipinas')).toBeInTheDocument();
        expect(
            screen.queryByText('Province of the Municipality'),
        ).not.toBeInTheDocument();
        expect(container.querySelector('article')).toHaveClass(
            'break-after-page',
            'last:break-after-auto',
        );
    });

    it('renders the trip ticket fields without screen chrome', () => {
        render(
            <TripTicketDocument
                travelOrder={makeTravelOrder()}
                tripTicket={makeTripTicket()}
                letterhead={letterhead}
            />,
        );

        expect(screen.getByText('TT-2026-00009')).toBeInTheDocument();
        expect(screen.getByText('ABC-1234')).toBeInTheDocument();
        expect(
            screen.getAllByText('Maria Clara Santos').length,
        ).toBeGreaterThan(0);
        expect(screen.getByText('Diesel')).toBeInTheDocument();
        expect(
            screen.getByText('Puerto Princesa City, Palawan'),
        ).toBeInTheDocument();
        expect(screen.getByText('8.40 L')).toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });
});
