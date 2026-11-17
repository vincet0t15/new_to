<?php

namespace App\Enums;

/**
 * How much a resubmission changed the travel order.
 *
 * A material change is one that an earlier approver could not have judged,
 * such as new dates, a different destination, or a different fund source. A
 * minor change is wording only, and never restarts the chain.
 */
enum TravelOrderChangeType: string
{
    case Minor = 'minor';
    case Material = 'material';

    public function label(): string
    {
        return match ($this) {
            self::Minor => 'Minor change',
            self::Material => 'Material change',
        };
    }
}
