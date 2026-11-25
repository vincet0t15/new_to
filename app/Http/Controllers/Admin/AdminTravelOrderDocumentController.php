<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
use App\Services\ItinerarySignatoryResolver;
use App\Services\SignatoryResolver;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only document view for administrators.
 *
 * The register is reachable with travel-orders.view-all, which is broader than
 * the owner/delivery check on the print routes. This page reuses the exact same
 * printed documents without widening who may issue a print.
 */
class AdminTravelOrderDocumentController extends Controller
{
    public function __construct(
        private readonly ItinerarySignatoryResolver $itinerarySignatories,
        private readonly SignatoryResolver $signatories,
    ) {
        //
    }

    public function show(TravelOrder $travelOrder): Response
    {
        $travelOrder->load([
            'employee.position',
            'employee.office',
            'office',
            'creator',
            'officialStation',
            'destinationLocation',
            'expenseTypes',
            'transportationModes',
            'itineraries.fromLocation',
            'itineraries.toLocation',
            'itineraries.transportationMode',
            'tripTickets.driver',
            'tripTickets.creator',
            'tripTickets.fuelTypeRecord',
        ]);

        return Inertia::render('admin/travel-orders/document', [
            'travelOrder' => $travelOrder,
            'letterhead' => config('travel-orders.letterhead'),
            'signatories' => $travelOrder->signatories
                ?? $this->signatories->for($travelOrder),
            'itinerarySignatories' => $this->itinerarySignatories->for($travelOrder),
            'tripTickets' => $travelOrder->tripTickets->all(),
            'urls' => [
                'register' => route('admin.travel-orders.index'),
            ],
        ]);
    }
}
