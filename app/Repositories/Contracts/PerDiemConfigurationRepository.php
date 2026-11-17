<?php
namespace App\Repositories\Contracts;
use App\Models\PerDiemConfiguration;
use Illuminate\Pagination\LengthAwarePaginator;
interface PerDiemConfigurationRepository { public function paginate(?string $s=null,int $p=15):LengthAwarePaginator; public function find(int $id):?PerDiemConfiguration; public function create(array $d):PerDiemConfiguration; public function update(PerDiemConfiguration $m,array $d):PerDiemConfiguration; public function delete(PerDiemConfiguration $m):void; }
