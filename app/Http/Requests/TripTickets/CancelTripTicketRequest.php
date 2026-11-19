<?php

namespace App\Http\Requests\TripTickets;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CancelTripTicketRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
