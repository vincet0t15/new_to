<?php

namespace App\Http\Requests\ExpenseTypes;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseTypeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:expense_types,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
            'is_transportation' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
