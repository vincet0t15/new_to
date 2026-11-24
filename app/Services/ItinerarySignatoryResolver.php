<?php

namespace App\Services;

use App\Models\TravelOrder;

/**
 * Works out who the printed Appendix A names at its two approval lines.
 *
 * The office the order is filed from may name an itinerary signatory group.
 * Whatever the group leaves blank falls back to the standard role title, so an
 * office without a group prints exactly as it always has. Read-only: both the
 * print page and the approval view call this to get the same answer.
 */
final class ItinerarySignatoryResolver
{
    /**
     * @return array{certified: array{name: string|null, title: string}, approved: array{name: string|null, title: string}}
     */
    public function for(TravelOrder $travelOrder): array
    {
        // The office may be loaded as a partial select, so re-read it through
        // the relation with the group's employee/position data attached.
        $group = $travelOrder->office()
            ->with([
                'itinerarySignatoryGroup.certifiedEmployee.position',
                'itinerarySignatoryGroup.approvedEmployee.position',
            ])
            ->first()?->itinerarySignatoryGroup;

        return [
            'certified' => [
                'name' => $group?->certifiedEmployee?->full_name,
                'title' => $group === null
                    ? 'Municipal Budget Officer'
                    : ($group->certified_title
                        ?? $group->certifiedEmployee?->position->name
                        ?? 'Municipal Budget Officer'),
            ],
            'approved' => [
                'name' => $group?->approvedEmployee?->full_name,
                'title' => $group === null
                    ? 'Municipal Mayor'
                    : ($group->approved_title
                        ?? $group->approvedEmployee?->position->name
                        ?? 'Municipal Mayor'),
            ],
        ];
    }
}
