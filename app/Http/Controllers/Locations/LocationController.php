<?php
namespace App\Http\Controllers\Locations;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Repositories\Contracts\LocationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class LocationController extends Controller {
    public function __construct(private readonly LocationRepository $repo){}
    public function index(Request $r):Response { return Inertia::render('locations/index',['locations'=>$this->repo->paginate($r->input('search')),'filters'=>['search'=>$r->input('search')??'']]); }
    public function create():Response { return Inertia::render('locations/create'); }
    public function store(\App\Http\Requests\Locations\StoreLocationRequest $r):RedirectResponse { $this->repo->create($r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Location created.')]); return redirect()->route('locations.index'); }
    public function edit(Location $location):Response { return Inertia::render('locations/edit',['location'=>$location]); }
    public function update(\App\Http\Requests\Locations\UpdateLocationRequest $r, Location $location):RedirectResponse { $this->repo->update($location,$r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Location updated.')]); return redirect()->route('locations.index'); }
    public function delete(Location $location):Response { return Inertia::render('locations/delete',['location'=>$location]); }
    public function destroy(Location $location):RedirectResponse { $this->repo->delete($location); Inertia::flash('toast',['type'=>'success','message'=>__('Location deleted.')]); return redirect()->route('locations.index'); }
}
