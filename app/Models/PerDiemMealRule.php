<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['per_diem_configuration_id', 'meal_type', 'name', 'amount', 'cutoff_time', 'sort_order'])]
class PerDiemMealRule extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cutoff_time' => 'datetime:H:i',
            'sort_order' => 'integer',
        ];
    }

    public function perDiemConfiguration(): BelongsTo
    {
        return $this->belongsTo(PerDiemConfiguration::class);
    }

    public function mealTypeOption(): BelongsTo
    {
        return $this->belongsTo(MealType::class, 'meal_type', 'code');
    }
}
