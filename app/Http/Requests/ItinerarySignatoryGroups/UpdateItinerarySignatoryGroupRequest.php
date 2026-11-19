<?php

namespace App\Http\Requests\ItinerarySignatoryGroups;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateItinerarySignatoryGroupRequest extends StoreItinerarySignatoryGroupRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('itinerary_signatory_groups', 'name')->ignore($this->route('itinerarySignatoryGroup')),
            ],
        ];
    }
}
