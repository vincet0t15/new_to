<?php

namespace App\Http\Requests\WorkflowStepTypes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkflowStepTypeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('workflow_step_types', 'name')],
            'signature_label' => ['nullable', 'string', 'max:255'],
            'signature_title' => ['nullable', 'string', 'max:255'],
            'is_signatory' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A step type with this name already exists.',
        ];
    }
}
