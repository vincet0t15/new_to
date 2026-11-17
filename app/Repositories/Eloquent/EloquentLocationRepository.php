<?php
namespace App\Repositories\Eloquent;
use App\Models\Location;
use App\Repositories\Contracts\LocationRepository;
use Illuminate\Pagination\LengthAwarePaginator;
class EloquentLocationRepository implements LocationRepository {
    public function paginate(?string $s=null,int $p=15):LengthAwarePaginator { return Location::query()->when($s,function($q)use($s){$q->where('name','like',"%$s%")->orWhere('description','like',"%$s%");})->orderBy('name')->paginate($p)->withQueryString(); }
    public function find(int $id):?Location { return Location::find($id); }
    public function create(array $d):Location { return Location::create($d); }
    public function update(Location $m,array $d):Location { $m->update($d); return $m; }
    public function delete(Location $m):void { $m->delete(); }
}
