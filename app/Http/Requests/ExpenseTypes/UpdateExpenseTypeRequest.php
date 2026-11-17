<?php
namespace App\Http\Requests\ExpenseTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateExpenseTypeRequest extends FormRequest { public function rules(): array { $m=$this->route('expenseType'); return ['name'=>['required','string','max:100',Rule::unique('expense_types','name')->ignore($m?->id)],'description'=>['nullable','string','max:255'],'is_default'=>['boolean'],'is_transportation'=>['boolean'],'is_active'=>['boolean']]; } }
