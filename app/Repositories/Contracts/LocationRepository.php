<?php
namespace App\Repositories\Contracts;
use App\Models\Location;
use Illuminate\Pagination\LengthAwarePaginator;
interface LocationRepository { public function paginate(?string $s=null,int $p=15):LengthAwarePaginator; public function find(int $id):?Location; public function create(array $d):Location; public function update(Location $m,array $d):Location; public function delete(Location $m):void; }
