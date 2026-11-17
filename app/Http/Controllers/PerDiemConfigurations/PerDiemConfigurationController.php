<?php
namespace App\Http\Controllers\PerDiemConfigurations;
use App\Http\Controllers\Controller;
use App\Models\PerDiemConfiguration;
use App\Repositories\Contracts\PerDiemConfigurationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class PerDiemConfigurationController extends Controller {
    public function __construct(private readonly PerDiemConfigurationRepository $repo){}
    public function index(Request $r):Response { return Inertia::render('per-diem-configurations/index',['perDiemConfigurations'=>$this->repo->paginate($r->input('search')),'filters'=>['search'=>$r->input('search')??'']]); }
    public function create():Response { return Inertia::render('per-diem-configurations/create'); }
    public function store(\App\Http\Requests\PerDiemConfigurations\StorePerDiemConfigurationRequest $r):RedirectResponse { $this->repo->create($r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Per diem configuration created.')]); return redirect()->route('per-diem-configurations.index'); }
    public function edit(PerDiemConfiguration $perDiemConfiguration):Response { return Inertia::render('per-diem-configurations/edit',['perDiemConfiguration'=>$perDiemConfiguration]); }
    public function update(\App\Http\Requests\PerDiemConfigurations\UpdatePerDiemConfigurationRequest $r, PerDiemConfiguration $perDiemConfiguration):RedirectResponse { $this->repo->update($perDiemConfiguration,$r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Per diem configuration updated.')]); return redirect()->route('per-diem-configurations.index'); }
    public function delete(PerDiemConfiguration $perDiemConfiguration):Response { return Inertia::render('per-diem-configurations/delete',['perDiemConfiguration'=>$perDiemConfiguration]); }
    public function destroy(PerDiemConfiguration $perDiemConfiguration):RedirectResponse { $this->repo->delete($perDiemConfiguration); Inertia::flash('toast',['type'=>'success','message'=>__('Per diem configuration deleted.')]); return redirect()->route('per-diem-configurations.index'); }
}
