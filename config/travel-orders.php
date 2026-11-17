<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Resubmission policy
    |--------------------------------------------------------------------------
    |
    | Material changes restart from the earliest configured workflow step that
    | reviews a changed field. Minor changes resume at the step that returned
    | the order. The review-field mapping is frozen when the order is submitted.
    |
    */

    'resubmission' => [
        'policy' => env('TRAVEL_ORDER_RESUBMISSION_POLICY', 'restart_from_first_affected'),

        /*
        | Fields an approver's decision depends on. Changing any of these is a
        | material change. Submission notes are tracked but are not material.
        */
        'material_fields' => [
            'purpose',
            'date_from',
            'date_to',
            'travel_classification_id',
            'fund_source_id',
            'origin_id',
            'destination_id',
            'official_station_id',
            'other_expenses',
            'expense_type_ids',
            'transportation_mode_ids',
            'employee_id',
        ],

        'tracked_fields' => [
            'purpose',
            'date_from',
            'date_to',
            'travel_classification_id',
            'fund_source_id',
            'origin_id',
            'destination_id',
            'official_station_id',
            'other_expenses',
            'expense_type_ids',
            'transportation_mode_ids',
            'employee_id',
            'submission_notes',
        ],

        'reviewable_fields' => [
            'purpose' => 'Travel purpose',
            'date_from' => 'Travel start date',
            'date_to' => 'Travel end date',
            'travel_classification_id' => 'Travel classification',
            'fund_source_id' => 'Fund source',
            'origin_id' => 'Origin',
            'destination_id' => 'Destination',
            'official_station_id' => 'Official station',
            'other_expenses' => 'Other expenses',
            'expense_type_ids' => 'Expense types',
            'transportation_mode_ids' => 'Transportation modes',
            'employee_id' => 'Traveler',
        ],
    ],

];
