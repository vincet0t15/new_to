<?php

namespace App\Http\Requests\TravelOrders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResubmitTravelOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'office_id' => ['nullable', 'integer', Rule::exists('offices', 'id')],
            'travel_classification' => ['nullable', 'string', 'max:100'],
            'fund_source' => ['nullable', 'string', 'max:100'],
            'purpose' => ['required', 'string', 'max:2000'],
            'destination' => ['required', 'string', 'max:255'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'submission_notes' => ['nullable', 'string', 'max:2000'],
            'revision_remarks' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'purpose.required' => 'Please state the purpose of the travel.',
            'destination.required' => 'Please enter the destination.',
            'date_from.required' => 'Please choose the travel start date.',
            'date_to.required' => 'Please choose the travel end date.',
            'date_to.after_or_equal' => 'The end date must be on or after the start date.',
            'revision_remarks.required' => 'Please describe what you changed before resubmitting.',
        ];
    }
}
