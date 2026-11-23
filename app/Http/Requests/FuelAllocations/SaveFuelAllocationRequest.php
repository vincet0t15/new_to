<?php

namespace App\Http\Requests\FuelAllocations;

use App\Models\FuelAllocation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveFuelAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in([FuelAllocation::TYPE_ALLOCATION, FuelAllocation::TYPE_TRANSFER])],
            'office_id' => ['required', 'integer', Rule::exists('offices', 'id')],
            'source_office_id' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('type') === FuelAllocation::TYPE_TRANSFER),
                'integer',
                'different:office_id',
                Rule::exists('offices', 'id'),
            ],
            'allocation_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'fuel_type_id' => ['nullable', 'integer', Rule::exists('fuel_types', 'id')],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'allocated_liters' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'allocated_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $liters = (float) ($this->input('allocated_liters') ?: 0);
            $amount = (float) ($this->input('allocated_amount') ?: 0);

            if ($liters <= 0 && $amount <= 0) {
                $validator->errors()->add('allocated_liters', 'Enter allocated liters or allocated amount.');
                $validator->errors()->add('allocated_amount', 'Enter allocated liters or allocated amount.');
            }
        }];
    }
}
