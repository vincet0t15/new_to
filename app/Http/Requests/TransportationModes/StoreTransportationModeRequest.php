<?php

namespace App\Http\Requests\TransportationModes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportationModeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:transportation_modes,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_government' => ['boolean'],
            'fix_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'is_active' => ['boolean'],
            'fares' => ['sometimes', 'array', 'max:100'],
            'fares.*.location_id' => ['required', 'integer', 'distinct', Rule::exists('locations', 'id')],
            'fares.*.amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}
