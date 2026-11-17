export type Office = {
    id: number;
    office_name: string;
    office_code: string | null;
    workflow_id: number | null;
    workflow?: {
        id: number;
        name: string;
        is_default: boolean;
        is_active: boolean;
    } | null;
};

export type WorkflowOfficeOption = {
    id: number;
    office_name: string;
    workflow_id: number | null;
    workflow: {
        id: number;
        name: string;
    } | null;
};

export type OfficeCreateRequest = {
    office_name: string;
    office_code: string;
};

export type OfficeUpdateRequest = Partial<OfficeCreateRequest>;
