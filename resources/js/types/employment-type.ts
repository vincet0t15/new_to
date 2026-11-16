export type EmploymentType = {
    id: number;
    name: string;
    description: string | null;
    positions_count?: number;
};

export type EmploymentTypeCreateRequest = {
    name: string;
    description: string;
};

export type EmploymentTypeUpdateRequest = Partial<EmploymentTypeCreateRequest>;
