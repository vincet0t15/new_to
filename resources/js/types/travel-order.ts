import type { WorkflowPreview } from './workflow';

export type TravelOrderStatus =
    | 'draft'
    | 'submitted'
    | 'approved'
    | 'returned'
    | 'cancelled'
    | 'completed'
    | 'rejected';

export type TravelOrderDeliveryStatus =
    | 'pending'
    | 'received'
    | 'acted'
    | 'closed';

export type TravelOrderDelivery = {
    id: number;
    travel_order_id: number;
    step_index: number;
    step_type_id: number | null;
    user_id: number;
    status: TravelOrderDeliveryStatus;
    received_at: string | null;
    acted_at: string | null;
    acted_via: string | null;
    user?: { id: number; name: string } | null;
};

export type TravelOrderAction = {
    id: number;
    step_index: number | null;
    step_label: string | null;
    event: string;
    revision_id: number | null;
    actor_user_id: number | null;
    remarks: string | null;
    meta: Record<string, unknown> | null;
    created_at: string | null;
    actor?: { id: number; name: string } | null;
    travelOrder?: {
        id: number;
        control_no: string | null;
        to_number: string | null;
    } | null;
};

export type TravelOrderRevision = {
    revision_number: number;
    returned_by: string | null;
    returned_at: string | null;
    return_remarks: string | null;
    returned_from_step: string | null;
    revised_by: string | null;
    revision_remarks: string | null;
    change_type: 'minor' | 'material';
    changed_fields: string[] | null;
    previous_data: Record<string, unknown> | null;
    new_data: Record<string, unknown> | null;
    resubmitted_at: string | null;
};

export type TravelOrderItinerary = {
    id: number;
    travel_order_id: number;
    date_from: string;
    date_to: string;
    departure_time: string;
    arrival_time: string | null;
    from_location_id: number | null;
    to_location_id: number | null;
    activity: string;
    reason: string | null;
    transportation_mode_id: number | null;
    transportation_fare: string | null;
    transportation_fare_is_auto: boolean;
    per_diem: string | null;
    per_diem_is_auto: boolean;
    registration_fee: string | null;
    created_at: string | null;
    updated_at: string | null;
    from_location?: TravelOrderLookupOption | null;
    to_location?: TravelOrderLookupOption | null;
    transportation_mode?: TravelOrderLookupOption | null;
};

export type TripTicketStatus = 'issued' | 'completed' | 'cancelled';

export type TripTicket = {
    id: number;
    travel_order_id: number;
    status: TripTicketStatus;
    vehicle_plate_no: string;
    vehicle_type: string | null;
    driver_id: number | null;
    fuel_type_id: number | null;
    fuel_type: string | null;
    fuel_source_office_id: number | null;
    fuel_efficiency: string | null;
    distance_km: string;
    round_trip: boolean;
    odometer_start: string | null;
    odometer_end: string | null;
    liters: string;
    fuel_price_per_liter: string | null;
    fuel_cost: string | null;
    fuel_station: string | null;
    fuel_receipt_no: string | null;
    fuel_receipt_path: string | null;
    fuel_receipt_url: string | null;
    fuel_consumed: string | null;
    remarks: string | null;
    ticket_no: string | null;
    created_by: number | null;
    completed_at: string | null;
    completed_by: number | null;
    cancelled_at: string | null;
    cancel_reason: string | null;
    created_at: string | null;
    updated_at: string | null;
    driver?: { id: number; full_name: string } | null;
    fuel_type_record?: { id: number; name: string } | null;
    fuel_source_office?: { id: number; office_name: string } | null;
    creator?: { id: number; name: string } | null;
    completer?: { id: number; name: string } | null;
};

export type TravelOrder = {
    id: number;
    control_no: string | null;
    to_number: string | null;
    employee_id: number | null;
    office_id: number | null;
    created_by: number;
    workflow_id: number | null;
    purpose: string | null;
    submission_notes: string | null;
    other_expenses: string | null;
    destination: string | null;
    date_from: string | null;
    date_to: string | null;
    travel_classification_id: number | null;
    fund_source_id: number | null;
    fund_source_assigned_by_user_id: number | null;
    fund_source_assigned_at: string | null;
    origin_id: number | null;
    destination_id: number | null;
    official_station_id: number | null;
    fund_source: string | TravelOrderLookupOption | null;
    travel_classification: string | TravelOrderLookupOption | null;
    earmarked_by_user_id: number | null;
    earmarked_at: string | null;
    earmark_number: string | null;
    earmark_remarks: string | null;
    recorded_by_user_id: number | null;
    recorded_at: string | null;
    recording_remarks: string | null;
    distributed_at: string | null;
    status: TravelOrderStatus;
    revision_number: number;
    returned_by_user_id: number | null;
    returned_from_step_index: number | null;
    returned_from_step: string | null;
    return_remarks: string | null;
    returned_at: string | null;
    last_resubmitted_at: string | null;
    routed_steps: number[] | null;
    current_step_index: number | null;
    recipient_user_id: number | null;
    reassign_candidates?: { id: number; name: string; username: string }[];
    submitted_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    employee?: {
        id: number;
        full_name?: string;
        monthly_salary?: string | null;
        position?: { id: number; name: string } | null;
        office?: { id: number; office_name: string } | null;
    } | null;
    office?: { id: number; office_name: string } | null;
    creator?: { id: number; name: string } | null;
    returnedBy?: { id: number; name: string } | null;
    origin?: TravelOrderLookupOption | null;
    destination_location?: TravelOrderLookupOption | null;
    official_station?: TravelOrderLookupOption | null;
    expense_types?: TravelOrderLookupOption[];
    transportation_modes?: TravelOrderLookupOption[];
    deliveries?: TravelOrderDelivery[];
    actions?: TravelOrderAction[];
    itineraries?: TravelOrderItinerary[];
    trip_tickets?: TripTicket[];
};

/**
 * One signature line as frozen on a submitted travel order.
 *
 * `title` is the workflow step's printed title, `position` the signer's own
 * position, and `title_override` a signatory group's replacement for both.
 */
export type TravelOrderSignatoryLine = {
    name: string | null;
    title: string | null;
    position: string | null;
    title_override: string | null;
};

/**
 * The travel order's printed signature lines, keyed by step meaning.
 */
export type TravelOrderSignatories = {
    department_head_approval: TravelOrderSignatoryLine;
    budget_office_approval: TravelOrderSignatoryLine;
    mayor_approval: TravelOrderSignatoryLine;
    oic_approval: TravelOrderSignatoryLine;
};

/**
 * The LGU letterhead printed on travel order and Appendix A sheets.
 */
export type Letterhead = {
    republic: string;
    province: string;
    municipality: string;
    office_name: string;
    official_station: string;
    station_line: string | null;
};

/**
 * The two Appendix A signature lines.
 */
export type ItinerarySignatories = {
    certified: { name: string | null; title: string };
    approved: { name: string | null; title: string };
};

export type TravelOrderRouteStepStatus =
    | 'planned'
    | 'upcoming'
    | 'current'
    | 'cleared'
    | 'skipped'
    | 'returned'
    | 'cancelled';

export type TravelOrderRouteStep = {
    index: number;
    label: string;
    office: string | null;
    is_signatory: boolean;
    status: TravelOrderRouteStepStatus;
    holders: {
        id: number;
        name: string;
        status: TravelOrderDeliveryStatus;
    }[];
};

export type TravelOrderTimelineEntry = {
    id: number;
    event: string;
    event_label: string;
    step_label: string | null;
    step_index: number | null;
    revision_id: number | null;
    actor: string | null;
    remarks: string | null;
    created_at: string | null;
};

export type TravelOrderAbilities = {
    submit: boolean;
    edit: boolean;
    resubmit: boolean;
    delete: boolean;
    manageItinerary: boolean;
    createTripTicket: boolean;
};

export type ApprovalAbilities = {
    receive: boolean;
    approve: boolean;
    acknowledge: boolean;
    return: boolean;
    forward: boolean;
    earmark: boolean;
    assignFundSource: boolean;
    record: boolean;
};

export type ApprovalInboxItem = {
    id: number;
    control_no: string | null;
    to_number: string | null;
    purpose: string | null;
    destination: string | null;
    date_from: string | null;
    date_to: string | null;
    submitted_at: string | null;
    employee: string | null;
    office: string | null;
    step_label: string | null;
    is_signatory: boolean;
    delivery_status: TravelOrderDeliveryStatus;
    url: string;
};

export type ApprovalHistoryItem = {
    id: number;
    control_no: string | null;
    to_number: string | null;
    purpose: string | null;
    destination: string | null;
    date_from: string | null;
    date_to: string | null;
    employee: string | null;
    office: string | null;
    step_label: string | null;
    your_action: string;
    your_action_event: string | null;
    action_at: string | null;
    delivery_status: TravelOrderDeliveryStatus;
    status: TravelOrderStatus;
    url: string;
};

export type TravelOrderLookupOption = {
    id: number;
    name: string;
    is_active?: boolean;
};

export type TravelOrderFormOptions = {
    offices: { id: number; office_name: string }[];
    employees: { id: number; full_name: string; office_id: number }[];
    classifications: TravelOrderLookupOption[];
    fundSources: TravelOrderLookupOption[];
    locations: TravelOrderLookupOption[];
    expenseTypes: TravelOrderLookupOption[];
    transportationModes: TravelOrderLookupOption[];
};

export type TripTicketTravelOrder = {
    id: number;
    control_no: string | null;
    to_number: string | null;
    purpose: string | null;
    date_from: string | null;
    date_to: string | null;
    status: TravelOrderStatus;
    office?: { id: number; office_name: string } | null;
    employee?: { id: number; full_name?: string } | null;
    origin?: TravelOrderLookupOption | null;
    destination?: TravelOrderLookupOption | null;
    itineraries?: {
        id: number;
        date_from: string;
        date_to: string;
        from_location?: TravelOrderLookupOption | null;
        to_location?: TravelOrderLookupOption | null;
    }[];
};

export type TripTicketDriver = {
    id: number;
    full_name: string;
};

export type TravelOrderShowOptions = {
    locations: TravelOrderLookupOption[];
    transportationModes: TravelOrderLookupOption[];
    drivers: TripTicketDriver[];
    offices: { id: number; office_name: string }[];
    fuelTypes: { id: number; name: string }[];
};

export type TravelOrderCreateRequest = {
    travel_classification_id: string;
    origin_id: string;
    destination_id: string;
    official_station_id: string;
    purpose: string;
    date_from: string;
    date_to: string;
    submission_notes: string;
    other_expenses: string;
    expense_type_ids: string[];
    transportation_mode_ids: string[];
    status: string;
};

export { type WorkflowPreview };
