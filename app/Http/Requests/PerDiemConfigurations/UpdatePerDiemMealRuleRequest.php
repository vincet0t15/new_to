<?php

namespace App\Http\Requests\PerDiemConfigurations;

use App\Models\MealType;
use App\Models\PerDiemMealRule;
use Illuminate\Validation\Rule;

class UpdatePerDiemMealRuleRequest extends StorePerDiemMealRuleRequest
{
    public function rules(): array
    {
        $current = $this->route('perDiemMealRule');
        $currentCode = $current instanceof PerDiemMealRule ? $current->meal_type : null;
        $submittedCode = $this->input('meal_type');
        $mealType = is_string($submittedCode)
            ? MealType::query()->where('code', $submittedCode)->first()
            : null;

        return [
            'meal_type' => [
                'required',
                'string',
                Rule::exists('meal_types', 'code')->where(function ($query) use ($currentCode) {
                    $query->where('is_active', true);

                    if ($currentCode !== null) {
                        $query->orWhere('code', $currentCode);
                    }
                }),
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
}
