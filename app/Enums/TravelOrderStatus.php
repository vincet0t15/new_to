<?php

namespace App\Enums;

/**
 * Lifecycle states of a travel order.
 */
enum TravelOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Returned => 'Returned for Revision',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * The states the register offers as a filter.
     *
     * @return array<int, self>
     */
    public static function selectable(): array
    {
        return [
            self::Draft,
            self::Submitted,
            self::Approved,
            self::Returned,
            self::Cancelled,
            self::Completed,
            self::Rejected,
        ];
    }

    /**
     * Whether the document is still moving through the chain.
     */
    public function isOpen(): bool
    {
        return $this === self::Submitted;
    }

    /**
     * Whether the document may still be edited by its creator.
     */
    public function isEditableByCreator(): bool
    {
        return $this === self::Draft || $this === self::Returned;
    }
}
