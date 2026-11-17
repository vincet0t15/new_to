<?php

namespace App\Http\Requests\TransportationModes;

use Illuminate\Foundation\Http\FormRequest;

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
            'fix_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
