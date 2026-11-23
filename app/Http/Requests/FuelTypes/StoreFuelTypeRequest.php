<?php

namespace App\Http\Requests\FuelTypes;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelTypeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:fuel_types,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
