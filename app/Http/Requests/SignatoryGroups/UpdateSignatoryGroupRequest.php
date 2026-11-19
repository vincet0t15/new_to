<?php

namespace App\Http\Requests\SignatoryGroups;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateSignatoryGroupRequest extends StoreSignatoryGroupRequest
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
                Rule::unique('signatory_groups', 'name')->ignore($this->route('signatoryGroup')),
            ],
        ];
    }
}
