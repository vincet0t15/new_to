<?php

namespace App\Http\Requests\SignatoryGroups;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSignatoryGroupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('signatory_groups', 'name')],
            // Each line may name an employee or carry only a title; a blank line
            // keeps the usual designation-derived signer.
            'certified_signatory_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'certified_signatory_title' => ['nullable', 'string', 'max:255'],
            'approved_signatory_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'approved_signatory_title' => ['nullable', 'string', 'max:255'],
            'oic_signatory_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'oic_signatory_title' => ['nullable', 'string', 'max:255'],
            // The offices whose printed travel orders use this group. An office
            // prints one group, so assigning it here moves it from whichever
            // group named it before.
            'office_ids' => ['nullable', 'array'],
            'office_ids.*' => ['integer', Rule::exists('offices', 'id')],
        ];
    }
}
