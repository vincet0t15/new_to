<?php
namespace App\Models;
use Database\Factories\TravelClassificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
#[Fillable(['name','description','is_active'])]
class TravelClassification extends Model {
    use HasFactory;
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function scopeActive(Builder $q): Builder { return $q->where('is_active',true); }
}
