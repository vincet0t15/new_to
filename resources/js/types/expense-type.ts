export type ExpenseType = {
    id: number;
    name: string;
    description: string | null;
    is_default: boolean;
    is_transportation: boolean;
    is_active: boolean;
    usage_count: number;
};

export type ExpenseTypeCreateRequest = {
    name: string;
    description: string;
    is_default: boolean;
    is_transportation: boolean;
    is_active: boolean;
};

export type ExpenseTypeUpdateRequest = Partial<ExpenseTypeCreateRequest>;
