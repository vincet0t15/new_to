<?php

namespace App\Http\Requests\Locations;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $location = $this->route('location');
        $locationId = $location instanceof Location ? $location->id : null;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('locations', 'name')->ignore($locationId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
