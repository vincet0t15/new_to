<?php

namespace App\Models;

use Database\Factories\ExpenseTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'is_default', 'is_transportation', 'is_active'])]
class ExpenseType extends Model
{
    /** @use HasFactory<ExpenseTypeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_transportation' => 'boolean', 'is_active' => 'boolean'];
    }

    /**
     * @param  Builder<ExpenseType>  $q
     * @return Builder<ExpenseType>
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}
