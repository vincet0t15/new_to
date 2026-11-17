<?php

namespace App\Http\Requests\TransportationModes;

use App\Models\TransportationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransportationModeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var TransportationMode|null $mode */
        $mode = $this->route('transportationMode');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('transportation_modes', 'name')->ignore($mode?->id)],
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
