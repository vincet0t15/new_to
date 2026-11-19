import type { OfficeSelectOption } from '@/types/office';

/**
 * The employee named to sign one of a group's two lines.
 *
 * The group serialises the eager-loaded signer relations, so only the fields
 * the list prints are spelled out here.
 */
export type ItinerarySignatoryGroupEmployee = {
    id: number;
    full_name: string;
};

/**
 * One active employee the group form can name on a line.
 *
 * The office is carried so two people of the same name can be told apart in
 * the picker.
 */
export type ItinerarySignatoryGroupEmployeeOption = {
    id: number;
    full_name: string;
    position: string | null;
    office_name: string | null;
};

/**
 * A reusable set of the Itinerary of Travel's two printed signature lines.
 *
 * Each line has an optional employee and an optional printed title; the
 * `*_employee` relations carry the resolved signer for display, and a null
 * relation means the line prints its standard role title over an empty name.
 * The `offices` list names every office that prints this group.
 */
export type ItinerarySignatoryGroup = {
    id: number;
    name: string;
    certified_employee_id: number | null;
    certified_title: string | null;
    certified_employee: ItinerarySignatoryGroupEmployee | null;
    approved_employee_id: number | null;
    approved_title: string | null;
    approved_employee: ItinerarySignatoryGroupEmployee | null;
    offices: OfficeSelectOption[];
};

export type ItinerarySignatoryGroupForm = {
    name: string;
    certified_employee_id: number | '';
    certified_title: string;
    approved_employee_id: number | '';
    approved_title: string;
    office_ids: number[];
};
