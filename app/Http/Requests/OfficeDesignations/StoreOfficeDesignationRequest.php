<?php

namespace App\Http\Requests\OfficeDesignations;

use App\Models\Employee;
use App\Models\OfficeDesignation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOfficeDesignationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'office_id' => ['required', 'integer', Rule::exists('offices', 'id')],
            'workflow_step_type_id' => ['required', 'integer', Rule::exists('workflow_step_types', 'id')],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'is_deputy' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * Validate the designation against the office it belongs to.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $officeId = $this->integer('office_id');
            $stepTypeId = $this->integer('workflow_step_type_id');
            $employeeId = $this->integer('employee_id');

            if ($officeId === 0 || $stepTypeId === 0 || $employeeId === 0) {
                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $belongsToOffice = Employee::query()
                ->whereKey($employeeId)
                ->where('office_id', $officeId)
                ->exists();

            if (! $belongsToOffice) {
                $validator->errors()->add(
                    'employee_id',
                    'The selected employee does not belong to this office.',
                );

                return;
            }

            if (! $this->boolean('is_active')) {
                return;
            }

            $duplicateQuery = OfficeDesignation::query()
                ->where('office_id', $officeId)
                ->where('workflow_step_type_id', $stepTypeId)
                ->where('employee_id', $employeeId)
                ->where('is_active', true);

            $current = $this->currentDesignation();

            if ($current !== null) {
                $duplicateQuery->whereKeyNot($current->getKey());
            }

            if ($duplicateQuery->exists()) {
                $validator->errors()->add(
                    'employee_id',
                    'This employee already holds an active designation for this office and step.',
                );
            }
        });
    }

    /**
     * The designation being updated, if this request updates one.
     */
    protected function currentDesignation(): ?OfficeDesignation
    {
        $designation = $this->route('designation');

        return $designation instanceof OfficeDesignation ? $designation : null;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.exists' => 'Select a valid employee.',
            'office_id.exists' => 'Select a valid office.',
            'workflow_step_type_id.exists' => 'Select a valid step type.',
        ];
    }
}
