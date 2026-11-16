import type { WorkflowPreview } from './workflow';

export type TravelOrderStatus =
    | 'draft'
    | 'submitted'
    | 'approved'
    | 'returned'
    | 'cancelled'
    | 'completed'
    | 'rejected';

export type TravelOrder = {
    id: number;
    control_no: string | null;
    to_number: string | null;
    employee_id: number | null;
    office_id: number | null;
    created_by: number;
    workflow_id: number | null;
    purpose: string | null;
    destination: string | null;
    date_from: string | null;
    date_to: string | null;
    fund_source: string | null;
    travel_classification: string | null;
    submission_notes: string | null;
    status: TravelOrderStatus;
    routed_steps: number[] | null;
    current_step_index: number | null;
    recipient_user_id: number | null;
    submitted_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    employee?: { id: number; full_name?: string } | null;
    office?: { id: number; office_name: string } | null;
};

export type TravelOrderFormOptions = {
    offices: { id: number; office_name: string }[];
    employees: { id: number; full_name: string; office_id: number }[];
    classifications: string[];
    fundSources: string[];
};

export type TravelOrderCreateRequest = {
    employee_id: number | '';
    office_id: number | '';
    travel_classification: string;
    fund_source: string;
    purpose: string;
    destination: string;
    date_from: string;
    date_to: string;
    submission_notes: string;
    status: string;
};

export type { WorkflowPreview };
