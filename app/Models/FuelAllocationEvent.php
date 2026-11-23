<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelAllocationEvent extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'fuel_allocation_id',
        'actor_user_id',
        'event',
        'remarks',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<FuelAllocation, $this>
     */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(FuelAllocation::class, 'fuel_allocation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
