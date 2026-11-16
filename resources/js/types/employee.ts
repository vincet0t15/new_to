import type { EmploymentType } from '@/types/employment-type';
import type { Office } from '@/types/office';
import type { Position } from '@/types/position';

export type Employee = {
    id: number;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    suffix: string | null;
    full_name: string;
    position_id: number | null;
    office_id: number;
    employment_type_id: number;
    contact_number: string | null;
    monthly_salary: string | null;
    image_path: string | null;
    photo_url: string | null;
    is_active: boolean;
    position: Pick<Position, 'id' | 'name'> | null;
    office: Pick<Office, 'id' | 'office_name'> | null;
    employment_type: Pick<EmploymentType, 'id' | 'name'> | null;
};

export type EmployeeCreateRequest = {
    first_name: string;
    middle_name: string;
    last_name: string;
    suffix: string;
    position_id: number | '';
    office_id: number | '';
    employment_type_id: number | '';
    contact_number: string;
    monthly_salary: string;
    is_active: boolean;
    photo: File | null;
    remove_photo: boolean;
};

export type EmployeeUpdateRequest = Partial<EmployeeCreateRequest>;
