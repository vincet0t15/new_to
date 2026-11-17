<?php

namespace App\Models;

use Database\Factories\TransportationModeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_government
 * @property string|null $fix_cost
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'is_government', 'fix_cost', 'is_active'])]
class TransportationMode extends Model
{
    /** @use HasFactory<TransportationModeFactory> */
    use HasFactory;

    public function fares(): HasMany
    {
        return $this->hasMany(TransportationFare::class);
    }

    public function fareTo(?int $toLocationId): ?string
    {
        if ($toLocationId === null) {
            return null;
        }

        $fare = $this->fares->firstWhere('location_id', $toLocationId);

        return $fare === null ? null : (string) $fare->amount;
    }

    protected function casts(): array
    {
        return [
            'is_government' => 'boolean',
            'is_active' => 'boolean',
            'fix_cost' => 'decimal:2',
        ];
    }

    /**
     * @param  Builder<TransportationMode>  $query
     * @return Builder<TransportationMode>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
