<?php

namespace App\Repositories\Contracts;

use App\Models\TravelClassification;
use Illuminate\Pagination\LengthAwarePaginator;

interface TravelClassificationRepository
{
    /**
     * @return LengthAwarePaginator<int, TravelClassification>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?TravelClassification;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TravelClassification;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TravelClassification $travelClassification, array $data): TravelClassification;

    public function usageCount(TravelClassification $travelClassification): int;

    public function delete(TravelClassification $travelClassification): void;
}
