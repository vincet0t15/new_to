<?php
namespace App\Http\Controllers\TravelClassifications;
use App\Http\Controllers\Controller;
use App\Models\TravelClassification;
use App\Repositories\Contracts\TravelClassificationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class TravelClassificationController extends Controller {
    public function __construct(private readonly TravelClassificationRepository $repo){}
    public function index(Request $r):Response { return Inertia::render('travel-classifications/index',['travelClassifications'=>$this->repo->paginate($r->input('search')),'filters'=>['search'=>$r->input('search')??'']]); }
    public function create():Response { return Inertia::render('travel-classifications/create'); }
    public function store(\App\Http\Requests\TravelClassifications\StoreTravelClassificationRequest $r):RedirectResponse { $this->repo->create($r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Travel classification created.')]); return redirect()->route('travel-classifications.index'); }
    public function edit(TravelClassification $travelClassification):Response { return Inertia::render('travel-classifications/edit',['travelClassification'=>$travelClassification]); }
    public function update(\App\Http\Requests\TravelClassifications\UpdateTravelClassificationRequest $r, TravelClassification $travelClassification):RedirectResponse { $this->repo->update($travelClassification,$r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Travel classification updated.')]); return redirect()->route('travel-classifications.index'); }
    public function delete(TravelClassification $travelClassification):Response { return Inertia::render('travel-classifications/delete',['travelClassification'=>$travelClassification]); }
    public function destroy(TravelClassification $travelClassification):RedirectResponse { $this->repo->delete($travelClassification); Inertia::flash('toast',['type'=>'success','message'=>__('Travel classification deleted.')]); return redirect()->route('travel-classifications.index'); }
}
