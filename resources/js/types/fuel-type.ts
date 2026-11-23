export type FuelType = {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    usage_count: number;
};

export type FuelTypeCreateRequest = {
    name: string;
    description: string;
    is_active: boolean;
};

export type FuelTypeUpdateRequest = Partial<FuelTypeCreateRequest>;
