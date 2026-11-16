export type Office = {
    id: number;
    office_name: string;
    office_code: string | null;
};

export type OfficeCreateRequest = {
    office_name: string;
    office_code: string;
};

export type OfficeUpdateRequest = Partial<OfficeCreateRequest>;
