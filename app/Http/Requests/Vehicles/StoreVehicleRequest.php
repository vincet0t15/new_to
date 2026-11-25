<?php

namespace App\Http\Requests\Vehicles;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'plate_no' => strtoupper(trim((string) $this->input('plate_no'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plate_no' => ['required', 'string', 'max:30', 'unique:vehicles,plate_no'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
