<?php

namespace App\Repositories\Contracts;

use App\Models\TravelClassification;
use Illuminate\Pagination\LengthAwarePaginator;

interface TravelClassificationRepository
{
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?TravelClassification;

    public function create(array $data): TravelClassification;

    public function update(TravelClassification $travelClassification, array $data): TravelClassification;

    public function usageCount(TravelClassification $travelClassification): int;

    public function delete(TravelClassification $travelClassification): void;
}
