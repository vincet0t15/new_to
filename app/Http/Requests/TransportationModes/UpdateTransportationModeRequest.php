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
            'fix_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
