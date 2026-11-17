<?php

namespace App\Models;

use Database\Factories\TravelClassificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'is_active'])]
class TravelClassification extends Model
{
    /** @use HasFactory<TravelClassificationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @param  Builder<TravelClassification>  $q
     * @return Builder<TravelClassification>
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}
