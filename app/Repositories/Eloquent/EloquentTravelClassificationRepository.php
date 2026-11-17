<?php

namespace App\Repositories\Eloquent;

use App\Models\TravelClassification;
use App\Models\TravelOrder;
use App\Repositories\Contracts\TravelClassificationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentTravelClassificationRepository implements TravelClassificationRepository
{
    /**
     * @return LengthAwarePaginator<int, TravelClassification>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return TravelClassification::query()
            ->select('travel_classifications.*')
            ->selectSub(
                TravelOrder::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('travel_classification_id', 'travel_classifications.id'),
                'usage_count',
            )
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?TravelClassification
    {
        return TravelClassification::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TravelClassification
    {
        return TravelClassification::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TravelClassification $travelClassification, array $data): TravelClassification
    {
        $travelClassification->update($data);

        return $travelClassification;
    }

    public function usageCount(TravelClassification $travelClassification): int
    {
        return TravelOrder::query()
            ->where(function ($query) use ($travelClassification): void {
                $query->where('travel_classification_id', $travelClassification->id)
                    ->orWhere('travel_classification', $travelClassification->name);
            })
            ->count();
    }

    public function delete(TravelClassification $travelClassification): void
    {
        $travelClassification->delete();
    }
}
