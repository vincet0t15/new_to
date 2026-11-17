export type TransportationFareInput = {
    location_id: string;
    amount: string;
};

export type TransportationFare = {
    id: number;
    location_id: number;
    amount: string;
    location?: { id: number; name: string } | null;
};

export type TransportationMode = {
    id: number;
    name: string;
    description: string | null;
    fix_cost: string | null;
    is_government: boolean;
    is_active: boolean;
    usage_count: number;
    fares: TransportationFare[];
};

export type TransportationModeCreateRequest = {
    name: string;
    description: string;
    fix_cost: string;
    is_government: boolean;
    is_active: boolean;
    fares: TransportationFareInput[];
};

export type TransportationModeUpdateRequest =
    Partial<TransportationModeCreateRequest>;
