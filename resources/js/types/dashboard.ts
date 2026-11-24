import type {
    TravelOrderDeliveryStatus,
    TravelOrderStatus,
} from './travel-order';

export type PersonalDashboardStats = {
    draft: number;
    submitted: number;
    returned: number;
    approved: number;
};

export type ProcessingDashboardStats = {
    pending: number;
    processed: number;
    returned: number;
};

export type DashboardTravelOrderItem = {
    id: number;
    control_no: string | null;
    to_number: string | null;
    purpose: string | null;
    destination: string | null;
    date_from?: string | null;
    date_to?: string | null;
    status?: TravelOrderStatus | null;
    updated_at?: string | null;
    submitted_at?: string | null;
    employee?: string | null;
    office?: string | null;
    delivery_status?: TravelOrderDeliveryStatus | null;
};

export type AdminDashboardStats = {
    draft: number;
    submitted: number;
    returned: number;
    approved: number;
    completed: number;
    cancelled: number;
    rejected: number;
};

export type StalledTravelOrderItem = {
    id: number;
    control_no: string | null;
    to_number: string | null;
    purpose: string | null;
    submitted_at: string | null;
    age_days: number;
    holder: string;
    step: string;
    employee?: string | null;
    office?: string | null;
};

export type FuelAlert = {
    office_id: number;
    office_name: string;
    fuel_type: string;
    remaining_liters: number;
    remaining_amount: number;
};

export type FuelSummary = {
    active_allocations: number;
    issued_trip_tickets: number;
    completed_trip_tickets: number;
};
