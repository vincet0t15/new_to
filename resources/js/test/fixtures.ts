import type {
    ApprovalAbilities,
    ItinerarySignatories,
    Letterhead,
    TravelOrder,
    TravelOrderSignatories,
    TripTicket,
} from '@/types/travel-order';

export function makeTravelOrder(
    overrides: Partial<TravelOrder> = {},
): TravelOrder {
    return {
        id: 1,
        control_no: 'TO-2026-0001',
        to_number: null,
        employee_id: 3,
        office_id: 4,
        created_by: 3,
        workflow_id: 1,
        purpose: 'Site inspection',
        submission_notes: null,
        other_expenses: null,
        destination: 'Puerto Princesa City, Palawan',
        date_from: '2026-11-25',
        date_to: '2026-11-26',
        travel_classification_id: 1,
        fund_source_id: null,
        fund_source_assigned_by_user_id: null,
        fund_source_assigned_at: null,
        origin_id: 1,
        destination_id: 2,
        official_station_id: null,
        fund_source: null,
        travel_classification: null,
        earmarked_by_user_id: null,
        earmarked_at: null,
        earmark_number: null,
        earmark_remarks: null,
        recorded_by_user_id: null,
        recorded_at: null,
        recording_remarks: null,
        distributed_at: null,
        status: 'submitted',
        revision_number: 1,
        returned_by_user_id: null,
        returned_from_step_index: null,
        returned_from_step: null,
        return_remarks: null,
        returned_at: null,
        last_resubmitted_at: null,
        routed_steps: [1, 2, 3],
        current_step_index: 0,
        recipient_user_id: 7,
        submitted_at: '2026-11-24T01:00:00.000Z',
        created_at: '2026-11-24T00:00:00.000Z',
        updated_at: '2026-11-24T01:00:00.000Z',
        employee: {
            id: 3,
            full_name: 'Maria Clara Santos',
            monthly_salary: null,
            position: null,
            office: null,
        },
        office: { id: 4, office_name: 'MEO' },
        reassign_candidates: [
            { id: 8, name: 'Tomas Aquino', username: 'deputy.engineer' },
        ],
        ...overrides,
    };
}

export function makeTripTicket(
    overrides: Partial<TripTicket> = {},
): TripTicket {
    return {
        id: 9,
        travel_order_id: 1,
        status: 'issued',
        vehicle_plate_no: 'ABC-1234',
        vehicle_type: 'Van',
        vehicle_id: null,
        driver_id: 3,
        fuel_type_id: 2,
        fuel_type: 'Diesel',
        fuel_source_office_id: null,
        fuel_efficiency: '10.00',
        distance_km: '42.00',
        round_trip: true,
        odometer_start: '1000.00',
        odometer_end: null,
        liters: '8.40',
        fuel_price_per_liter: '60.00',
        fuel_cost: '504.00',
        fuel_station: null,
        fuel_receipt_no: null,
        fuel_receipt_path: null,
        fuel_receipt_url: null,
        fuel_consumed: null,
        remarks: null,
        ticket_no: 'TT-2026-00009',
        created_by: 3,
        completed_at: null,
        completed_by: null,
        cancelled_at: null,
        cancel_reason: null,
        created_at: '2026-11-24T00:30:00.000Z',
        updated_at: '2026-11-24T00:30:00.000Z',
        driver: { id: 3, full_name: 'Maria Clara Santos' },
        fuel_type_record: { id: 2, name: 'Diesel' },
        fuel_source_office: null,
        creator: { id: 3, name: 'Maria Clara Santos' },
        completer: null,
        ...overrides,
    };
}

export const letterhead: Letterhead = {
    republic: 'Republic of the Philippines',
    province: 'Palawan',
    municipality: 'San Vicente',
    office_name: 'Municipality of San Vicente',
    official_station: 'San Vicente, Palawan',
    station_line: null,
};

export const signatories: TravelOrderSignatories = {
    department_head_approval: {
        name: 'Pedro Bautista',
        title: 'Department Head',
        position: 'Engineer',
        title_override: null,
    },
    budget_office_approval: {
        name: null,
        title: 'Budget Officer',
        position: null,
        title_override: null,
    },
    mayor_approval: {
        name: null,
        title: 'Mayor',
        position: null,
        title_override: null,
    },
    oic_approval: {
        name: null,
        title: 'OIC',
        position: null,
        title_override: null,
    },
};

export const itinerarySignatories: ItinerarySignatories = {
    certified: { name: 'Maria Clara Santos', title: 'Employee' },
    approved: { name: 'Pedro Bautista', title: 'Department Head' },
};

export function makeAbilities(
    overrides: Partial<ApprovalAbilities> = {},
): ApprovalAbilities {
    return {
        receive: false,
        approve: false,
        acknowledge: false,
        return: false,
        forward: false,
        earmark: false,
        assignFundSource: false,
        record: false,
        ...overrides,
    };
}
