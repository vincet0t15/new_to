<?php

namespace App\Repositories\Contracts;

use App\Models\ItinerarySignatoryGroup;
use Illuminate\Pagination\LengthAwarePaginator;

interface ItinerarySignatoryGroupRepository
{
    /**
     * @return LengthAwarePaginator<int, ItinerarySignatoryGroup>
     */
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ItinerarySignatoryGroup;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ItinerarySignatoryGroup $itinerarySignatoryGroup, array $data): ItinerarySignatoryGroup;

    public function delete(ItinerarySignatoryGroup $itinerarySignatoryGroup): void;
}
