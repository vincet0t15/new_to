<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeAccountRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->getKey() : null;

        return [
            'user_id' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->whereNull('employee_id')
                        ->orWhere('employee_id', $employeeId)
                ),
            ],
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
            'user_id.exists' => 'That account already belongs to another employee.',
        ];
    }
}
