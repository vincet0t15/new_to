<?php

namespace App\Http\Requests\TripTickets;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTripTicketRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_plate_no' => ['required', 'string', 'max:30'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'driver_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'fuel_efficiency' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'distance_km' => ['required', 'numeric', 'min:0', 'max:999999'],
            'round_trip' => ['boolean'],
            'odometer_start' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'fuel_price_per_liter' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'fuel_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'fuel_station' => ['nullable', 'string', 'max:150'],
            'fuel_receipt_no' => ['nullable', 'string', 'max:100'],
        ];
    }
}
