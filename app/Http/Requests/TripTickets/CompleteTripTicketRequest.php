<?php

namespace App\Http\Requests\TripTickets;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteTripTicketRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'odometer_end' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'fuel_consumed' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'fuel_price_per_liter' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'fuel_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'fuel_station' => ['nullable', 'string', 'max:150'],
            'fuel_receipt_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}
