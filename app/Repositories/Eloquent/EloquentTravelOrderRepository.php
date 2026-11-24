<?php

namespace App\Repositories\Eloquent;

use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Models\TravelOrder;
use App\Models\TravelOrderAction;
use App\Models\TravelOrderDelivery;
use App\Models\User;
use App\Repositories\Contracts\TravelOrderRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentTravelOrderRepository implements TravelOrderRepository
{
    public function paginateForCreator(User $user, ?string $search = null, int $perPage = 15, ?TravelOrderStatus $status = null): LengthAwarePaginator
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
            ->when($status !== null, fn ($query) => $query->where('status', $status))
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
        return $this->inboxQuery($user)
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

    public function pendingInboxCount(User $user): int
    {
        return $this->inboxQuery($user)->count();
    }

    public function stalledPending(int $limit = 8, ?int $stalledAfterDays = null): Collection
    {
        return $this->stalledQuery($stalledAfterDays)
            ->with([
                'office:id,office_name',
                'employee:id,first_name,middle_name,last_name,suffix',
                'recipient:id,name',
            ])
            ->orderBy('submitted_at')
            ->limit($limit)
            ->get();
    }

    public function stalledPendingCount(?int $stalledAfterDays = null): int
    {
        return $this->stalledQuery($stalledAfterDays)->count();
    }

    public function countStatusesForCreator(User $user): array
    {
        $counts = TravelOrder::query()
            ->forCreator($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = array_fill_keys(
            array_column(TravelOrderStatus::cases(), 'value'),
            0,
        );

        foreach ($counts as $status => $total) {
            if (is_string($status) && is_numeric($total)) {
                $byStatus[$status] = (int) $total;
            }
        }

        return [
            'draft' => $byStatus[TravelOrderStatus::Draft->value],
            'submitted' => $byStatus[TravelOrderStatus::Submitted->value],
            'returned' => $byStatus[TravelOrderStatus::Returned->value],
            'approved' => $byStatus[TravelOrderStatus::Approved->value],
        ];
    }

    public function processingCounts(User $user): array
    {
        return [
            'pending' => $this->inboxQuery($user)->count(),
            'processed' => TravelOrderDelivery::query()
                ->where('user_id', $user->id)
                ->where('status', TravelOrderDeliveryStatus::Acted)
                ->distinct()
                ->count('travel_order_id'),
            'returned' => TravelOrderAction::query()
                ->where('actor_user_id', $user->id)
                ->where('event', WorkflowEvent::Returned)
                ->distinct()
                ->count('travel_order_id'),
        ];
    }

    /**
     * The live queue: submitted orders with an open copy at the current step.
     * Shared by the inbox listing and its dashboard count so the two cannot
     * drift apart.
     *
     * @return Builder<TravelOrder>
     */
    private function inboxQuery(User $user): Builder
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
            });
    }

    /**
     * The admin-wide version of the live queue: submitted orders with an open
     * delivery at their current step, optionally older than a monitoring
     * threshold. Ordering is done by the caller so the same query can feed both
     * the widget and its total count.
     *
     * @return Builder<TravelOrder>
     */
    private function stalledQuery(?int $stalledAfterDays): Builder
    {
        return TravelOrder::query()
            ->where('status', TravelOrderStatus::Submitted)
            ->whereNotNull('current_step_index')
            ->whereNotNull('submitted_at')
            ->when($stalledAfterDays !== null, fn (Builder $query) => $query
                ->where('submitted_at', '<=', now()->subDays($stalledAfterDays)))
            ->whereHas('deliveries', fn ($query) => $query
                ->open()
                ->whereColumn(
                    'travel_order_deliveries.step_index',
                    'travel_orders.current_step_index',
                ));
    }

    public function paginateHistory(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return TravelOrder::query()
            ->whereHas('deliveries', fn ($query) => $query->where('user_id', $user->id))
            ->with([
                'office:id,office_name',
                'employee:id,first_name,middle_name,last_name,suffix',
                'deliveries' => fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->orderByDesc('id'),
                'actions' => fn ($query) => $query
                    ->where('actor_user_id', $user->id)
                    ->orderByDesc('id'),
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
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
