<?php
namespace App\Models;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
#[Fillable(['name','description','is_active'])]
class Location extends Model {
    use HasFactory;
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function scopeActive(Builder $q): Builder { return $q->where('is_active',true); }
    public function transportationFares(): HasMany { return $this->hasMany(TransportationFare::class); }
    public function perDiemConfigurations(): BelongsToMany { return $this->belongsToMany(PerDiemConfiguration::class, 'per_diem_configuration_location'); }
}
