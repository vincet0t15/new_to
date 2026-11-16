<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentEmployeeRepository implements EmployeeRepository
{
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator
    {
        return Employee::query()
            ->with([
                'office:id,office_name',
                'employmentType:id,name',
                'position:id,name',
            ])
            ->when($search !== null && $search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('middle_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('suffix', 'like', '%'.$search.'%')
                        ->orWhere('contact_number', 'like', '%'.$search.'%')
                        ->orWhereHas('position', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('office', function (Builder $query) use ($search): void {
                            $query->where('office_name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee;
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }
}
