<?php

namespace App\Repositories\Contracts;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface TravelOrderRepository
{
    /**
     * @return LengthAwarePaginator<int, TravelOrder>
     */
    public function paginateForCreator(User $user, ?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function findForCreator(User $user, int $id): ?TravelOrder;

    /**
     * Travel orders returned to this account for revision.
     *
     * @return LengthAwarePaginator<int, TravelOrder>
     */
    public function paginateReturnedForCreator(User $user, ?string $search = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Travel orders waiting for this account's action.
     *
     * @return LengthAwarePaginator<int, TravelOrder>
     */
    public function paginateInbox(User $user, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TravelOrder;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TravelOrder $travelOrder, array $data): TravelOrder;
}
