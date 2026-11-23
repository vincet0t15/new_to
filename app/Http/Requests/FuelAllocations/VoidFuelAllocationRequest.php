<?php

namespace App\Http\Requests\FuelAllocations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VoidFuelAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'void_reason' => ['required', 'string', 'max:255'],
        ];
    }
}
