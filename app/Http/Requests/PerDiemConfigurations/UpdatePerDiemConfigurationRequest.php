<?php

namespace App\Http\Requests\PerDiemConfigurations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerDiemConfigurationRequest extends FormRequest
{
    public function rules(): array
    {
        $configuration = $this->route('perDiemConfiguration');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('per_diem_configurations', 'name')->ignore($configuration?->id)],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'distinct', Rule::exists('locations', 'id')],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'daily_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'is_active' => ['boolean'],
        ];
    }
}
