<?php

namespace App\Http\Requests\Workflows;

use App\Models\Workflow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWorkflowRequest extends StoreWorkflowRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workflow = $this->route('workflow');

        return [
            ...parent::rules(),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workflows', 'name')->ignore($workflow),
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('workflows', 'slug')->ignore($workflow),
            ],
        ];
    }

    /**
     * Keep one active workflow marked as the fallback at all times.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $workflow = $this->route('workflow');

            if (! $workflow instanceof Workflow) {
                return;
            }

            if ($workflow->is_default && ! $this->boolean('is_default')) {
                $validator->errors()->add(
                    'is_default',
                    'Set another workflow as the default before removing this one.',
                );
            }

            if ($workflow->is_default && ! $this->boolean('is_active')) {
                $validator->errors()->add(
                    'is_active',
                    'The default workflow must stay active.',
                );
            }
        });
    }
}
