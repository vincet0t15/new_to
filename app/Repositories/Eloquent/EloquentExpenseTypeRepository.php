<?php
namespace App\Repositories\Eloquent;
use App\Models\ExpenseType;
use App\Repositories\Contracts\ExpenseTypeRepository;
use Illuminate\Pagination\LengthAwarePaginator;
class EloquentExpenseTypeRepository implements ExpenseTypeRepository {
    public function paginate(?string $s=null,int $p=15):LengthAwarePaginator { return ExpenseType::query()->when($s,function($q)use($s){$q->where('name','like',"%$s%")->orWhere('description','like',"%$s%");})->orderBy('name')->paginate($p)->withQueryString(); }
    public function find(int $id):?ExpenseType { return ExpenseType::find($id); }
    public function create(array $d):ExpenseType { return ExpenseType::create($d); }
    public function update(ExpenseType $m,array $d):ExpenseType { $m->update($d); return $m; }
    public function delete(ExpenseType $m):void { $m->delete(); }
}
