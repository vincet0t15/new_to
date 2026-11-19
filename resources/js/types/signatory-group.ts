import type { OfficeSelectOption } from '@/types/office';

/**
 * The employee named to sign one of a group's three lines.
 *
 * The group serialises the eager-loaded signer relations, so only the fields
 * the list prints are spelled out here.
 */
export type SignatoryGroupEmployee = {
    id: number;
    full_name: string;
};

/**
 * One active employee the group form can name on a line.
 *
 * The office is carried so two people of the same name can be told apart in
 * the picker.
 */
export type SignatoryGroupEmployeeOption = {
    id: number;
    full_name: string;
    position: string | null;
    office_name: string | null;
};

/**
 * A reusable set of the travel order's three printed signature lines.
 *
 * Each line has an optional employee and an optional printed title; the
 * `*_signatory` relations carry the resolved signer for display, and a null
 * relation means the line keeps its usual designation-derived signer. The
 * `offices` list names every office that prints this group.
 */
export type SignatoryGroup = {
    id: number;
    name: string;
    certified_signatory_employee_id: number | null;
    certified_signatory_title: string | null;
    certified_signatory: SignatoryGroupEmployee | null;
    approved_signatory_employee_id: number | null;
    approved_signatory_title: string | null;
    approved_signatory: SignatoryGroupEmployee | null;
    oic_signatory_employee_id: number | null;
    oic_signatory_title: string | null;
    oic_signatory: SignatoryGroupEmployee | null;
    offices: OfficeSelectOption[];
};

export type SignatoryGroupForm = {
    name: string;
    certified_signatory_employee_id: number | '';
    certified_signatory_title: string;
    approved_signatory_employee_id: number | '';
    approved_signatory_title: string;
    oic_signatory_employee_id: number | '';
    oic_signatory_title: string;
    office_ids: number[];
};
