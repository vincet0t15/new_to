<?php

namespace App\Http\Requests\Workflows;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkflowStepRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workflow_step_type_id' => ['required', 'integer', Rule::exists('workflow_step_types', 'id')],
            'label' => ['nullable', 'string', 'max:255'],
            'office_id' => ['nullable', 'integer', Rule::exists('offices', 'id')],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'auto_skip_creator' => ['sometimes', 'boolean'],
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
            'workflow_step_type_id.exists' => 'Select a valid step type.',
            'office_id.exists' => 'Select a valid office.',
        ];
    }
}
