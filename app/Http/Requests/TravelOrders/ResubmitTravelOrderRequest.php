<?php

namespace App\Http\Requests\TravelOrders;

/**
 * A resubmission posts the same header fields as the edit form, plus the
 * remarks that explain what changed. The shared rules keep both screens from
 * drifting apart.
 */
class ResubmitTravelOrderRequest extends StoreTravelOrderRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'revision_remarks' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'revision_remarks.required' => 'Please describe what you changed before resubmitting.',
        ];
    }
}
