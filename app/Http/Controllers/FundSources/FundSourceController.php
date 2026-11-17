<?php
namespace App\Http\Controllers\FundSources;
use App\Http\Controllers\Controller;
use App\Models\FundSource;
use App\Repositories\Contracts\FundSourceRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class FundSourceController extends Controller {
    public function __construct(private readonly FundSourceRepository $repo){}
    public function index(Request $r):Response { return Inertia::render('fund-sources/index',['fundSources'=>$this->repo->paginate($r->input('search')),'filters'=>['search'=>$r->input('search')??'']]); }
    public function create():Response { return Inertia::render('fund-sources/create'); }
    public function store(\App\Http\Requests\FundSources\StoreFundSourceRequest $r):RedirectResponse { $this->repo->create($r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Fund source created.')]); return redirect()->route('fund-sources.index'); }
    public function edit(FundSource $fundSource):Response { return Inertia::render('fund-sources/edit',['fundSource'=>$fundSource]); }
    public function update(\App\Http\Requests\FundSources\UpdateFundSourceRequest $r, FundSource $fundSource):RedirectResponse { $this->repo->update($fundSource,$r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Fund source updated.')]); return redirect()->route('fund-sources.index'); }
    public function delete(FundSource $fundSource):Response { return Inertia::render('fund-sources/delete',['fundSource'=>$fundSource]); }
    public function destroy(FundSource $fundSource):RedirectResponse { $this->repo->delete($fundSource); Inertia::flash('toast',['type'=>'success','message'=>__('Fund source deleted.')]); return redirect()->route('fund-sources.index'); }
}
