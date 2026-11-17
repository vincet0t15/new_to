<?php

namespace App\Http\Requests\WorkflowStepTypes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateWorkflowStepTypeRequest extends StoreWorkflowStepTypeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $stepType = $this->route('stepType');

        return [
            ...parent::rules(),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workflow_step_types', 'name')->ignore($stepType),
            ],
        ];
    }
}
