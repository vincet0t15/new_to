export type TravelClassification = {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    usage_count: number;
};

export type TravelClassificationCreateRequest = {
    name: string;
    description: string;
    is_active: boolean;
};

export type TravelClassificationUpdateRequest =
    Partial<TravelClassificationCreateRequest>;
