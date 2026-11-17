<?php

namespace App\Repositories\Eloquent;

use App\Enums\TravelOrderStatus;
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

    public function paginateReturnedForCreator(User $user, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return TravelOrder::query()
            ->forCreator($user)
            ->where('status', TravelOrderStatus::Returned)
            ->with([
                'office:id,office_name',
                'employee:id,first_name,last_name',
                'returnedBy:id,name',
            ])
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('purpose', 'like', '%'.$search.'%')
                        ->orWhere('destination', 'like', '%'.$search.'%')
                        ->orWhere('to_number', 'like', '%'.$search.'%')
                        ->orWhere('control_no', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('returned_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateInbox(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return TravelOrder::query()
            ->where('status', TravelOrderStatus::Submitted)
            ->whereNotNull('current_step_index')
            ->whereHas('deliveries', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->open()
                    ->whereColumn(
                        'travel_order_deliveries.step_index',
                        'travel_orders.current_step_index',
                    );
            })
            ->with([
                'office:id,office_name',
                'employee:id,first_name,middle_name,last_name,suffix',
                'deliveries' => fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->open(),
            ])
            ->orderByDesc('submitted_at')
            ->paginate($perPage)
            ->withQueryString();
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
