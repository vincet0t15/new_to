<?php

namespace App\Http\Requests\Employees;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'office_id' => ['required', 'integer', Rule::exists('offices', 'id')],
            'employment_type_id' => ['required', 'integer', Rule::exists('employment_types', 'id')],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['boolean'],
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
            'position_id.exists' => 'The selected position is invalid.',
            'office_id.exists' => 'The selected office is invalid.',
            'employment_type_id.exists' => 'The selected employment type is invalid.',
            'photo.image' => 'The photo must be an image.',
            'photo.mimes' => 'The photo must be a jpg, jpeg, png, or webp file.',
            'photo.max' => 'The photo may not be larger than 2 MB.',
        ];
    }
}
