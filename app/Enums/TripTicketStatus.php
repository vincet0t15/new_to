<?php

namespace App\Enums;

/**
 * Lifecycle states of a trip ticket.
 *
 * A ticket is issued as soon as it is prepared: it is the paper the driver
 * carries, so there is no separate signature step on this simplified flow.
 * Once the trip is over the ticket is completed with the post-trip figures;
 * a ticket that will not be used is cancelled instead.
 */
enum TripTicketStatus: string
{
    case Issued = 'issued';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Issued',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether the ticket's figures may still be corrected.
     */
    public function isEditable(): bool
    {
        return $this === self::Issued;
    }
}
