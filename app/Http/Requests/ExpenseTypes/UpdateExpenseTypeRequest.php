<?php

namespace App\Http\Requests\ExpenseTypes;

use App\Models\ExpenseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseTypeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $expenseType = $this->route('expenseType');
        $expenseTypeId = $expenseType instanceof ExpenseType ? $expenseType->id : null;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('expense_types', 'name')->ignore($expenseTypeId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
            'is_transportation' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
