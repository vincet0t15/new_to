<?php

namespace App\Repositories\Eloquent;

use App\Models\ItinerarySignatoryGroup;
use App\Models\Office;
use App\Repositories\Contracts\ItinerarySignatoryGroupRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentItinerarySignatoryGroupRepository implements ItinerarySignatoryGroupRepository
{
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator
    {
        return ItinerarySignatoryGroup::query()
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            // The list prints the signer on each line and the offices the group
            // covers, so those relations are resolved up front rather than once
            // per row.
            ->with([
                'certifiedEmployee',
                'approvedEmployee',
                'offices:id,office_name,itinerary_signatory_group_id',
            ])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): ItinerarySignatoryGroup
    {
        $officeIds = $this->officeIds($data);

        unset($data['office_ids']);

        return DB::transaction(function () use ($data, $officeIds): ItinerarySignatoryGroup {
            $itinerarySignatoryGroup = ItinerarySignatoryGroup::create($data);

            $this->assignOffices($itinerarySignatoryGroup, $officeIds);

            return $itinerarySignatoryGroup;
        });
    }

    public function update(ItinerarySignatoryGroup $itinerarySignatoryGroup, array $data): ItinerarySignatoryGroup
    {
        // Leaving office_ids out keeps the current offices; an empty array
        // clears them. The form always sends the field.
        $syncOffices = array_key_exists('office_ids', $data);
        $officeIds = $this->officeIds($data);

        unset($data['office_ids']);

        return DB::transaction(function () use ($itinerarySignatoryGroup, $data, $syncOffices, $officeIds): ItinerarySignatoryGroup {
            $itinerarySignatoryGroup->update($data);

            if ($syncOffices) {
                $this->assignOffices($itinerarySignatoryGroup, $officeIds);
            }

            return $itinerarySignatoryGroup;
        });
    }

    public function delete(ItinerarySignatoryGroup $itinerarySignatoryGroup): void
    {
        $itinerarySignatoryGroup->delete();
    }

    /**
     * Point the selected offices at the group and release the rest.
     *
     * An office prints one itinerary group, so assigning an office that named
     * another group deliberately moves it here.
     *
     * @param  list<int>  $officeIds
     */
    private function assignOffices(ItinerarySignatoryGroup $itinerarySignatoryGroup, array $officeIds): void
    {
        Office::query()
            ->where('itinerary_signatory_group_id', $itinerarySignatoryGroup->id)
            ->when($officeIds !== [], function ($query) use ($officeIds) {
                $query->whereNotIn('id', $officeIds);
            })
            ->update(['itinerary_signatory_group_id' => null]);

        if ($officeIds === []) {
            return;
        }

        Office::query()
            ->whereIn('id', $officeIds)
            ->update(['itinerary_signatory_group_id' => $itinerarySignatoryGroup->id]);
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
