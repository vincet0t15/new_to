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
    origin_id: number | null;
    destination_id: number | null;
    official_station_id: number | null;
    fund_source: string | null;
    travel_classification: string | null;
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
    submitted_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    employee?: { id: number; full_name?: string } | null;
    office?: { id: number; office_name: string } | null;
    creator?: { id: number; name: string } | null;
    returnedBy?: { id: number; name: string } | null;
    travelClassification?: TravelOrderLookupOption | null;
    fundSource?: TravelOrderLookupOption | null;
    origin?: TravelOrderLookupOption | null;
    destinationLocation?: TravelOrderLookupOption | null;
    officialStation?: TravelOrderLookupOption | null;
    expenseTypes?: TravelOrderLookupOption[];
    transportationModes?: TravelOrderLookupOption[];
    deliveries?: TravelOrderDelivery[];
    actions?: TravelOrderAction[];
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
};

export type ApprovalAbilities = {
    receive: boolean;
    approve: boolean;
    acknowledge: boolean;
    return: boolean;
    forward: boolean;
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

export type TravelOrderCreateRequest = {
    employee_id: number | '';
    office_id: number | '';
    travel_classification_id: string;
    fund_source_id: string;
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
