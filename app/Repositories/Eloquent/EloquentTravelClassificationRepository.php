<?php
namespace App\Repositories\Eloquent;
use App\Models\TravelClassification;
use App\Repositories\Contracts\TravelClassificationRepository;
use Illuminate\Pagination\LengthAwarePaginator;
class EloquentTravelClassificationRepository implements TravelClassificationRepository {
    public function paginate(?string $s=null,int $p=15):LengthAwarePaginator { return TravelClassification::query()->when($s,function($q)use($s){$q->where('name','like',"%$s%")->orWhere('description','like',"%$s%");})->orderBy('name')->paginate($p)->withQueryString(); }
    public function find(int $id):?TravelClassification { return TravelClassification::find($id); }
    public function create(array $d):TravelClassification { return TravelClassification::create($d); }
    public function update(TravelClassification $m,array $d):TravelClassification { $m->update($d); return $m; }
    public function delete(TravelClassification $m):void { $m->delete(); }
}
