<?php

namespace App\Http\Requests\PerDiemConfigurations;

use App\Models\MealType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerDiemMealRuleRequest extends FormRequest
{
    public function rules(): array
    {
        $submittedCode = $this->input('meal_type');
        $mealType = is_string($submittedCode)
            ? MealType::query()->where('code', $submittedCode)->first()
            : null;

        return [
            'meal_type' => [
                'required',
                'string',
                Rule::exists('meal_types', 'code')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'cutoff_time' => [
                'nullable',
                'date_format:H:i',
                Rule::prohibitedUnless($mealType?->has_cutoff === true),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return ['cutoff_time.prohibited' => 'This meal type does not accept a cutoff time.'];
    }
}
