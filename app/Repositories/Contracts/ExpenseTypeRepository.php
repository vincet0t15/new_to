<?php
namespace App\Repositories\Contracts;
use App\Models\ExpenseType;
use Illuminate\Pagination\LengthAwarePaginator;
interface ExpenseTypeRepository { public function paginate(?string $s=null,int $p=15):LengthAwarePaginator; public function find(int $id):?ExpenseType; public function create(array $d):ExpenseType; public function update(ExpenseType $m,array $d):ExpenseType; public function delete(ExpenseType $m):void; }
