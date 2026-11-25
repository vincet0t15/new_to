<?php

namespace App\Http\Requests\Vehicles;

use App\Models\Vehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
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
        /** @var Vehicle $vehicle */
        $vehicle = $this->route('vehicle');

        return [
            'plate_no' => [
                'required',
                'string',
                'max:30',
                Rule::unique('vehicles', 'plate_no')->ignore($vehicle->id),
            ],
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
