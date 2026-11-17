export type Location = {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    usage_count: number;
};

export type LocationCreateRequest = {
    name: string;
    description: string;
    is_active: boolean;
};

export type LocationUpdateRequest = Partial<LocationCreateRequest>;
