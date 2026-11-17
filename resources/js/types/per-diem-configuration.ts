export type MealTypeOption = {
    code: string;
    name: string;
    has_cutoff: boolean;
    is_active: boolean;
};

export type PerDiemConfiguration = {
    id: number;
    name: string;
    effective_from: string | null;
    effective_until: string | null;
    is_active: boolean;
    usage_count: number;
    daily_rate?: string | null;
    locations?: { id: number; name: string }[];
};

export type PerDiemConfigurationCreateRequest = {
    name: string;
    location_ids: number[];
    effective_from: string;
    effective_until: string;
    is_active: boolean;
};

export type PerDiemConfigurationUpdateRequest =
    Partial<PerDiemConfigurationCreateRequest>;

export type MealRuleMealType = {
    id: number;
    code: string;
    name: string;
};

export type MealRule = {
    id: number;
    per_diem_configuration_id: number;
    meal_type: string;
    meal_type_option: MealRuleMealType | null;
    name: string;
    amount: string;
    cutoff_time: string | null;
    sort_order: number;
};

export type MealRuleInput = {
    meal_type: string;
    amount: string;
    cutoff_time: string;
    sort_order: string;
};
