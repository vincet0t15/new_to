<?php

namespace App\Http\Requests\EmploymentTypes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateEmploymentTypeRequest extends StoreEmploymentTypeRequest
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
                Rule::unique('employment_types', 'name')->ignore($this->route('employmentType')),
            ],
        ];
    }
}
