export type Vehicle = {
    id: number;
    plate_no: string;
    vehicle_type: string | null;
    model: string | null;
    capacity: number | null;
    driver_id: number | null;
    is_active: boolean;
    usage_count: number;
    driver?: { id: number; full_name: string } | null;
};

export type VehicleCreateRequest = {
    plate_no: string;
    vehicle_type: string;
    model: string;
    capacity: string;
    driver_id: string;
    is_active: boolean;
};

export type VehicleOption = {
    id: number;
    plate_no: string;
    vehicle_type: string | null;
    model: string | null;
    capacity: number | null;
    driver_id: number | null;
};
