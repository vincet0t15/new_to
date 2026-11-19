<?php

namespace App\Http\Requests\TravelOrders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTravelOrderItineraryRequest extends FormRequest
{
    /**
     * Ceiling on the numeric fields so a malformed post cannot store an
     * absurd amount that overflows the decimal columns.
     */
    private const MAX_AMOUNT = '9999999999.99';

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'departure_time' => ['required', 'date_format:H:i'],
            'arrival_time' => ['nullable', 'date_format:H:i'],
            'from_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'to_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'activity' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'transportation_mode_id' => ['nullable', 'integer', Rule::exists('transportation_modes', 'id')],
            'transportation_fare' => ['nullable', 'numeric', 'min:0', 'max:'.self::MAX_AMOUNT],
            'transportation_fare_is_auto' => ['boolean'],
            'per_diem' => ['nullable', 'numeric', 'min:0', 'max:'.self::MAX_AMOUNT],
            'per_diem_is_auto' => ['boolean'],
            'registration_fee' => ['nullable', 'numeric', 'min:0', 'max:'.self::MAX_AMOUNT],
        ];
    }
}
