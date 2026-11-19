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
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}
