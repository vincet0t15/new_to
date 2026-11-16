<?php

namespace App\Repositories\Eloquent;

use App\Models\TravelOrder;
use App\Models\User;
use App\Repositories\Contracts\TravelOrderRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentTravelOrderRepository implements TravelOrderRepository
{
    public function paginateForCreator(User $user, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return TravelOrder::query()
            ->forCreator($user)
            ->with(['office:id,office_name', 'employee:id,first_name,last_name'])
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('purpose', 'like', '%'.$search.'%')
                        ->orWhere('destination', 'like', '%'.$search.'%')
                        ->orWhere('to_number', 'like', '%'.$search.'%')
                        ->orWhere('control_no', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForCreator(User $user, int $id): ?TravelOrder
    {
        return TravelOrder::query()
            ->forCreator($user)
            ->with(['office', 'employee', 'workflow', 'deliveries.user', 'actions.actor'])
            ->find($id);
    }

    public function create(array $data): TravelOrder
    {
        return TravelOrder::create($data);
    }

    public function update(TravelOrder $travelOrder, array $data): TravelOrder
    {
        $travelOrder->update($data);

        return $travelOrder;
    }
}
