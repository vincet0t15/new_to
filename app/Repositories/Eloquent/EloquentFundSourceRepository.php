<?php

namespace App\Repositories\Eloquent;

use App\Models\FundSource;
use App\Models\TravelOrder;
use App\Repositories\Contracts\FundSourceRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentFundSourceRepository implements FundSourceRepository
{
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = FundSource::query()
            ->select('fund_sources.*')
            ->selectSub(
                TravelOrder::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('fund_source', 'fund_sources.name'),
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

        return $paginator;
    }

    public function find(int $id): ?FundSource
    {
        return FundSource::query()->find($id);
    }

    public function create(array $data): FundSource
    {
        return FundSource::query()->create($data);
    }

    public function update(FundSource $fundSource, array $data): FundSource
    {
        $fundSource->update($data);

        return $fundSource;
    }

    public function usageCount(FundSource $fundSource): int
    {
        return TravelOrder::query()->where('fund_source', $fundSource->name)->count();
    }

    public function delete(FundSource $fundSource): void
    {
        $fundSource->delete();
    }
}
