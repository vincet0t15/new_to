<?php
namespace App\Repositories\Contracts;
use App\Models\FundSource;
use Illuminate\Pagination\LengthAwarePaginator;
interface FundSourceRepository { public function paginate(?string $s=null,int $p=15):LengthAwarePaginator; public function find(int $id):?FundSource; public function create(array $d):FundSource; public function update(FundSource $m,array $d):FundSource; public function delete(FundSource $m):void; }
