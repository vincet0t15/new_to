<?php
namespace App\Repositories\Eloquent;
use App\Models\PerDiemConfiguration;
use App\Repositories\Contracts\PerDiemConfigurationRepository;
use Illuminate\Pagination\LengthAwarePaginator;
class EloquentPerDiemConfigurationRepository implements PerDiemConfigurationRepository {
    public function paginate(?string $s=null,int $p=15):LengthAwarePaginator { return PerDiemConfiguration::query()->when($s,function($q)use($s){$q->where('name','like',"%$s%");})->orderBy('name')->paginate($p)->withQueryString(); }
    public function find(int $id):?PerDiemConfiguration { return PerDiemConfiguration::find($id); }
    public function create(array $d):PerDiemConfiguration { return PerDiemConfiguration::create($d); }
    public function update(PerDiemConfiguration $m,array $d):PerDiemConfiguration { $m->update($d); return $m; }
    public function delete(PerDiemConfiguration $m):void { $m->delete(); }
}
