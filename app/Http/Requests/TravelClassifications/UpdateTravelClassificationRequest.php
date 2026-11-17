<?php

namespace App\Http\Requests\TravelClassifications;

use App\Models\TravelClassification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTravelClassificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $travelClassification = $this->route('travelClassification');
        $travelClassificationId = $travelClassification instanceof TravelClassification ? $travelClassification->id : null;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('travel_classifications', 'name')->ignore($travelClassificationId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
