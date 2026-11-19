<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $per_diem_configuration_id
 * @property string $meal_type
 * @property string $name
 * @property string $amount
 * @property Carbon|null $cutoff_time
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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

    /**
     * @return BelongsTo<PerDiemConfiguration, $this>
     */
    public function perDiemConfiguration(): BelongsTo
    {
        return $this->belongsTo(PerDiemConfiguration::class);
    }

    /**
     * @return BelongsTo<MealType, $this>
     */
    public function mealTypeOption(): BelongsTo
    {
        return $this->belongsTo(MealType::class, 'meal_type', 'code');
    }
}
