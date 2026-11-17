<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $amount
 */
#[Fillable(['transportation_mode_id', 'location_id', 'amount'])]
class TransportationFare extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<TransportationMode, $this>
     */
    public function transportationMode(): BelongsTo
    {
        return $this->belongsTo(TransportationMode::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
