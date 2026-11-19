<?php

namespace App\Http\Requests\ItinerarySignatoryGroups;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItinerarySignatoryGroupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('itinerary_signatory_groups', 'name')],
            // Each line may name an employee or carry only a title; a blank line
            // prints its standard role title over an empty name.
            'certified_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'certified_title' => ['nullable', 'string', 'max:255'],
            'approved_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'approved_title' => ['nullable', 'string', 'max:255'],
            // The offices whose Appendix A uses this group. An office prints one
            // itinerary group, so assigning it here moves it from whichever
            // group named it before.
            'office_ids' => ['nullable', 'array'],
            'office_ids.*' => ['integer', Rule::exists('offices', 'id')],
        ];
    }
}
