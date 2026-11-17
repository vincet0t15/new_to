<?php

namespace App\Http\Requests\TravelOrders;

use App\Models\ExpenseType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTravelOrderRequest extends FormRequest
{
    /**
     * Ceilings on the repeating parts of the header form.
     *
     * Every selected id is a database lookup, so a malformed post cannot ask
     * the server to resolve an unbounded list. The limits sit far above what a
     * real municipal trip needs.
     */
    private const MAX_TRANSPORTATION_MODES = 20;

    private const MAX_EXPENSE_TYPES = 50;

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
            'travel_classification_id' => ['required', 'integer', Rule::exists('travel_classifications', 'id')],
            'fund_source_id' => ['nullable', 'integer', Rule::exists('fund_sources', 'id')],
            'origin_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'destination_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'official_station_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'purpose' => ['required', 'string', 'max:2000'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'transportation_mode_ids' => ['sometimes', 'array', 'max:'.self::MAX_TRANSPORTATION_MODES],
            'transportation_mode_ids.*' => ['integer', Rule::exists('transportation_modes', 'id')],
            'expense_type_ids' => ['sometimes', 'array', 'max:'.self::MAX_EXPENSE_TYPES],
            'expense_type_ids.*' => ['integer', Rule::exists('expense_types', 'id')],
            'other_expenses' => [
                Rule::requiredIf(fn (): bool => $this->othersExpenseSelected()),
                'nullable',
                'string',
                'max:255',
            ],
            'submission_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', 'in:draft,submitted'],
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
            'purpose.required' => 'Please state the purpose of the travel.',
            'travel_classification_id.required' => 'Please choose a travel classification.',
            'destination_id.required' => 'Please choose the destination.',
            'date_from.required' => 'Please choose the travel start date.',
            'date_to.required' => 'Please choose the travel end date.',
            'date_to.after_or_equal' => 'The end date must be on or after the start date.',
            'other_expenses.required' => 'Please specify the other expenses.',
        ];
    }

    /**
     * The "Others" expense row needs the text the approver will read.
     */
    private function othersExpenseSelected(): bool
    {
        $input = $this->input('expense_type_ids', []);

        if (! is_array($input)) {
            return false;
        }

        $ids = array_filter($input, fn (mixed $id): bool => is_numeric($id));

        if ($ids === []) {
            return false;
        }

        return ExpenseType::query()
            ->whereIn('id', $ids)
            ->where('name', 'Others')
            ->exists();
    }
}
