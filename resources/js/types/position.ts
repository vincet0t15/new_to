import type { EmploymentType } from '@/types/employment-type';

export type Position = {
    id: number;
    name: string;
    description: string | null;
    employment_type_id: number | null;
    employment_type: Pick<EmploymentType, 'id' | 'name'> | null;
};

export type PositionCreateRequest = {
    name: string;
    description: string;
    employment_type_id: number | '';
};

export type PositionUpdateRequest = Partial<PositionCreateRequest>;
