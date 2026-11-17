export type FundSource = {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    usage_count: number;
};

export type FundSourceCreateRequest = {
    name: string;
    description: string;
    is_active: boolean;
};

export type FundSourceUpdateRequest = Partial<FundSourceCreateRequest>;
