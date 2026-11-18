<?php

namespace App\Repositories\Eloquent;

use App\Models\OfficeDesignation;
use App\Repositories\Contracts\OfficeDesignationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentOfficeDesignationRepository implements OfficeDesignationRepository
{
    public function paginate(
        ?int $officeId,
        ?int $stepTypeId,
        int $perPage = 50,
    ): LengthAwarePaginator {
        return OfficeDesignation::query()
            ->with([
                'office:id,office_name',
                'stepType:id,name,is_signatory',
                'employee:id,first_name,middle_name,last_name,suffix,office_id',
                'employee.office:id,office_name',
                'employee.user:id,employee_id',
            ])
            ->when($officeId !== null, fn ($query) => $query->where('office_id', $officeId))
            ->when($stepTypeId !== null, fn ($query) => $query->where('workflow_step_type_id', $stepTypeId))
            ->orderBy('office_id')
            ->orderBy('workflow_step_type_id')
            ->orderBy('is_deputy')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): OfficeDesignation
    {
        return OfficeDesignation::create($data);
    }

    public function update(OfficeDesignation $designation, array $data): OfficeDesignation
    {
        $designation->update($data);

        return $designation;
    }

    public function delete(OfficeDesignation $designation): void
    {
        $designation->delete();
    }
}
