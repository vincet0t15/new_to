export type WorkflowOption = {
    id: number;
    name: string;
    is_default: boolean;
    is_active: boolean;
};

export type WorkflowSummary = WorkflowOption & {
    slug: string;
    description: string | null;
    steps_count?: number;
    offices_count?: number;
};

export type WorkflowStepType = {
    id: number;
    name: string;
    signature_label: string | null;
    signature_title: string | null;
    is_signatory: boolean;
    sort_order: number;
    is_active: boolean;
    steps_count?: number;
    designations_count?: number;
};

export type WorkflowStep = {
    id: number;
    workflow_id: number;
    workflow_step_type_id: number;
    label: string | null;
    office_id: number | null;
    sort_order: number;
    auto_skip_creator: boolean;
    reviewed_fields: string[] | null;
    is_active: boolean;
    step_type: Pick<
        WorkflowStepType,
        'id' | 'name' | 'is_signatory' | 'signature_label' | 'signature_title'
    >;
    office: { id: number; office_name: string } | null;
};

export type Workflow = WorkflowSummary & {
    steps: WorkflowStep[];
};

export type WorkflowCreateRequest = {
    name: string;
    slug: string;
    description: string;
    is_default: boolean;
    is_active: boolean;
};

export type WorkflowUpdateRequest = WorkflowCreateRequest & {
    assigned_office_ids: number[];
};

export type WorkflowStepRequest = {
    workflow_step_type_id: number | '';
    label: string;
    office_id: number | '';
    sort_order: number;
    auto_skip_creator: boolean;
    reviewed_fields: string[];
    is_active: boolean;
};

export type WorkflowStepTypeRequest = {
    name: string;
    signature_label: string;
    signature_title: string;
    is_signatory: boolean;
    sort_order: number;
    is_active: boolean;
};

export type WorkflowPreviewDesignee = {
    id: number;
    employee_id: number;
    name: string;
    is_deputy: boolean;
    has_account: boolean;
};

export type WorkflowPreviewStep = {
    id: number;
    label: string;
    step_type: {
        id: number;
        name: string;
        is_signatory: boolean;
    };
    office: {
        id: number;
        office_name: string;
    };
    uses_filing_office: boolean;
    auto_skip_creator: boolean;
    is_ready: boolean;
    warning: string | null;
    designees: WorkflowPreviewDesignee[];
};

export type WorkflowPreview = {
    office: {
        id: number;
        office_name: string;
    };
    workflow: {
        id: number;
        name: string;
        slug: string;
        is_default: boolean;
        is_active: boolean;
    } | null;
    uses_override: boolean;
    fallback_reason: string | null;
    warning: string | null;
    is_ready: boolean;
    steps: WorkflowPreviewStep[];
};

export type OfficeDesignation = {
    id: number;
    office_id: number;
    workflow_step_type_id: number;
    employee_id: number;
    is_deputy: boolean;
    is_active: boolean;
    starts_at: string | null;
    ends_at: string | null;
    office: {
        id: number;
        office_name: string;
    };
    step_type: {
        id: number;
        name: string;
        is_signatory: boolean;
    };
    employee: {
        id: number;
        full_name: string;
        office_id: number;
        office: { id: number; office_name: string } | null;
        user: { id: number } | null;
    };
};

export type OfficeDesignationRequest = {
    office_id: number | '';
    workflow_step_type_id: number | '';
    employee_id: number | '';
    is_deputy: boolean;
    is_active: boolean;
    starts_at: string;
    ends_at: string;
};

export type DesignationEmployeeOption = {
    id: number;
    full_name: string;
    office_id: number;
    office_name: string;
    has_account: boolean;
};

export type DesignationImpactOrder = {
    id: number;
    control_no: string | null;
    to_number: string | null;
    purpose: string | null;
    age_days: number;
    holder: string;
    step: string;
};

export type DesignationImpact = {
    designation: {
        id: number;
        office: string;
        step: string;
        holder: string;
    };
    orders: DesignationImpactOrder[];
    candidates: {
        id: number;
        name: string;
        username: string;
    }[];
};
