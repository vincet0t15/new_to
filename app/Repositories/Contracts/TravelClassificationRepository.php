<?php
namespace App\Repositories\Contracts;
use App\Models\TravelClassification;
use Illuminate\Pagination\LengthAwarePaginator;
interface TravelClassificationRepository { public function paginate(?string $s=null,int $p=15):LengthAwarePaginator; public function find(int $id):?TravelClassification; public function create(array $d):TravelClassification; public function update(TravelClassification $m,array $d):TravelClassification; public function delete(TravelClassification $m):void; }
