<?php
namespace App\Repositories\Eloquent;
use App\Models\FundSource;
use App\Repositories\Contracts\FundSourceRepository;
use Illuminate\Pagination\LengthAwarePaginator;
class EloquentFundSourceRepository implements FundSourceRepository {
    public function paginate(?string $s=null,int $p=15):LengthAwarePaginator { return \App\Models\FundSource::query()->when($s,function($q)use($s){$q->where('name','like',"%$s%")->orWhere('description','like',"%$s%");})->orderBy('name')->paginate($p)->withQueryString(); }
    public function find(int $id):?\App\Models\FundSource { return \App\Models\FundSource::find($id); }
    public function create(array $d):\App\Models\FundSource { return \App\Models\FundSource::create($d); }
    public function update(\App\Models\FundSource $m,array $d):\App\Models\FundSource { $m->update($d); return $m; }
    public function delete(\App\Models\FundSource $m):void { $m->delete(); }
}
