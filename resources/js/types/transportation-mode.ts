export type TransportationMode = {
    id: number;
    name: string;
    description: string | null;
    is_government: boolean;
    fix_cost: string | null;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
};

export type TransportationModeCreateRequest = {
    name: string;
    description: string;
    is_government: boolean;
    fix_cost: string;
    is_active: boolean;
};
