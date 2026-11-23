<?php

namespace App\Http\Requests\FuelTypes;

use App\Models\FuelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFuelTypeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $fuelType = $this->route('fuelType');
        $fuelTypeId = $fuelType instanceof FuelType ? $fuelType->id : null;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('fuel_types', 'name')->ignore($fuelTypeId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
