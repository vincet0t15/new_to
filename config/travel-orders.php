<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Resubmission policy
    |--------------------------------------------------------------------------
    |
    | When a returned travel order is resubmitted, the default is to resume at
    | the step that returned it. The other policies only apply when the
    | creator made a material change. `restart` starts the frozen chain again;
    | `restart_from_first_affected` starts at the earliest step that the
    | changed fields can affect.
    |
    */

    'resubmission' => [
        'policy' => env('TRAVEL_ORDER_RESUBMISSION_POLICY', 'resume'),

        /*
        | Fields an approver's decision depends on. Changing any of these is a
        | material change. Submission notes are tracked but are not material.
        */
        'material_fields' => [
            'purpose',
            'destination',
            'date_from',
            'date_to',
            'travel_classification',
            'fund_source',
            'employee_id',
        ],

        'tracked_fields' => [
            'purpose',
            'destination',
            'date_from',
            'date_to',
            'travel_classification',
            'fund_source',
            'employee_id',
            'submission_notes',
        ],

        /*
        | Which step types a changed field affects, used only by the
        | `restart_from_first_affected` policy. A field missing here is treated
        | as affecting the whole chain, so the safe fallback is step zero.
        */
        'affected_step_types' => [
            'purpose' => ['department_head_approval'],
            'destination' => ['department_head_approval'],
            'date_from' => ['department_head_approval'],
            'date_to' => ['department_head_approval'],
            'travel_classification' => ['department_head_approval'],
            'employee_id' => ['department_head_approval'],
            'fund_source' => ['budget_review'],
        ],
    ],

];
