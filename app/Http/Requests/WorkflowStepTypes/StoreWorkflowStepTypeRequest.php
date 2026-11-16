<?php

namespace App\Http\Requests\WorkflowStepTypes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
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
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('workflow_step_types', 'slug')],
            'signature_label' => ['nullable', 'string', 'max:255'],
            'signature_title' => ['nullable', 'string', 'max:255'],
            'is_signatory' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Give a step type a stable slug when the form leaves it blank.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && is_string($this->input('name'))) {
            $this->merge(['slug' => Str::slug($this->input('name'), '_')]);
        }
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
            'slug.unique' => 'A step type with this slug already exists.',
        ];
    }
}
