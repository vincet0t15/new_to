<?php

namespace App\Repositories\Contracts;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TravelOrderRepository
{
    /**
     * @return LengthAwarePaginator<int, TravelOrder>
     */
    public function paginateForCreator(User $user, ?string $search = null, int $perPage = 15, ?TravelOrderStatus $status = null): LengthAwarePaginator;

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
     * How many travel orders are waiting for this account's action right now.
     * Uses the same live-queue query as the inbox so the badge and the listing
     * cannot drift apart.
     */
    public function pendingInboxCount(User $user): int;

    /**
     * Submitted orders that have been waiting at their current step longer
     * than the monitoring threshold, oldest first. Used only by the admin
     * dashboard; it does not affect routing or authorization.
     *
     * @return Collection<int, TravelOrder>
     */
    public function stalledPending(int $limit = 8, ?int $stalledAfterDays = null): Collection;

    /**
     * Total number of submitted orders past the stalled-approval threshold.
     */
    public function stalledPendingCount(?int $stalledAfterDays = null): int;

    /**
     * Every travel order that was ever delivered to this account, newest
     * activity first. Unlike the inbox this keeps documents after they were
     * acted on, so the account can always find what passed through it.
     *
     * @return LengthAwarePaginator<int, TravelOrder>
     */
    public function paginateHistory(User $user, int $perPage = 15): LengthAwarePaginator;

    /**
     * Counts of the account's own travel orders, keyed by lifecycle status.
     * Only the statuses a dashboard displays are returned.
     *
     * @return array{draft: int, submitted: int, returned: int, approved: int}
     */
    public function countStatusesForCreator(User $user): array;

    /**
     * Counts for a processing workspace: the live queue, distinct orders this
     * account acted on, and distinct orders it sent back for revision. A person
     * can hold more than one step of an order, so copies and actions are not
     * the same thing as orders here.
     *
     * @return array{pending: int, processed: int, returned: int}
     */
    public function processingCounts(User $user): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TravelOrder;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TravelOrder $travelOrder, array $data): TravelOrder;
}
