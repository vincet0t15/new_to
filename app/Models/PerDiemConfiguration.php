<?php
namespace App\Models;
use Database\Factories\PerDiemConfigurationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
#[Fillable(['name','effective_from','effective_until','daily_rate','is_active'])]
class PerDiemConfiguration extends Model {
    use HasFactory;
    protected function casts(): array { return ['effective_from'=>'date','effective_until'=>'date','daily_rate'=>'decimal:2','is_active'=>'boolean']; }
    public function scopeActive(Builder $q): Builder { return $q->where('is_active',true); }
    public function locations(): BelongsToMany { return $this->belongsToMany(Location::class, 'per_diem_configuration_location'); }
    public function perDiemMealRules(): HasMany { return $this->hasMany(PerDiemMealRule::class)->orderBy('sort_order')->orderBy('id'); }
}
