<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;

interface EmployeeRepository
{
    /**
     * @return LengthAwarePaginator<int, Employee>
     */
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee;

    public function delete(Employee $employee): void;
}
