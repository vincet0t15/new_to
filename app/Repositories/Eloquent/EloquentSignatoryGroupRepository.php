<?php

namespace App\Repositories\Eloquent;

use App\Models\Office;
use App\Models\SignatoryGroup;
use App\Repositories\Contracts\SignatoryGroupRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentSignatoryGroupRepository implements SignatoryGroupRepository
{
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator
    {
        return SignatoryGroup::query()
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            // The list prints the signer on each line and the offices the group
            // covers, so those relations are resolved up front rather than once
            // per row.
            ->with([
                'certifiedSignatory',
                'approvedSignatory',
                'oicSignatory',
                'offices:id,office_name,signatory_group_id',
            ])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): SignatoryGroup
    {
        $officeIds = $this->officeIds($data);

        unset($data['office_ids']);

        return DB::transaction(function () use ($data, $officeIds): SignatoryGroup {
            $signatoryGroup = SignatoryGroup::create($data);

            $this->assignOffices($signatoryGroup, $officeIds);

            return $signatoryGroup;
        });
    }

    public function update(SignatoryGroup $signatoryGroup, array $data): SignatoryGroup
    {
        // Leaving office_ids out keeps the current offices; an empty array
        // clears them. The form always sends the field.
        $syncOffices = array_key_exists('office_ids', $data);
        $officeIds = $this->officeIds($data);

        unset($data['office_ids']);

        return DB::transaction(function () use ($signatoryGroup, $data, $syncOffices, $officeIds): SignatoryGroup {
            $signatoryGroup->update($data);

            if ($syncOffices) {
                $this->assignOffices($signatoryGroup, $officeIds);
            }

            return $signatoryGroup;
        });
    }

    public function delete(SignatoryGroup $signatoryGroup): void
    {
        $signatoryGroup->delete();
    }

    /**
     * Point the selected offices at the group and release the rest.
     *
     * An office prints one travel order group, so assigning an office that
     * named another group deliberately moves it here.
     *
     * @param  list<int>  $officeIds
     */
    private function assignOffices(SignatoryGroup $signatoryGroup, array $officeIds): void
    {
        Office::query()
            ->where('signatory_group_id', $signatoryGroup->id)
            ->when($officeIds !== [], function ($query) use ($officeIds) {
                $query->whereNotIn('id', $officeIds);
            })
            ->update(['signatory_group_id' => null]);

        if ($officeIds === []) {
            return;
        }

        Office::query()
            ->whereIn('id', $officeIds)
            ->update(['signatory_group_id' => $signatoryGroup->id]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function officeIds(array $data): array
    {
        $officeIds = $data['office_ids'] ?? [];

        return is_array($officeIds)
            ? array_values(array_map('intval', $officeIds))
            : [];
    }
}
