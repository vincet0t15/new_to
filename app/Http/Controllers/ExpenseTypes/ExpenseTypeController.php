<?php
namespace App\Http\Controllers\ExpenseTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransportationModes\StoreTransportationModeRequest;
use App\Models\ExpenseType;
use App\Repositories\Contracts\ExpenseTypeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class ExpenseTypeController extends Controller {
    public function __construct(private readonly ExpenseTypeRepository $repo){}
    public function index(Request $r):Response { return Inertia::render('expense-types/index',['expenseTypes'=>$this->repo->paginate($r->input('search')),'filters'=>['search'=>$r->input('search')??'']]); }
    public function create():Response { return Inertia::render('expense-types/create'); }
    public function store(\App\Http\Requests\ExpenseTypes\StoreExpenseTypeRequest $r):RedirectResponse { $this->repo->create($r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Expense type created.')]); return redirect()->route('expense-types.index'); }
    public function edit(ExpenseType $expenseType):Response { return Inertia::render('expense-types/edit',['expenseType'=>$expenseType]); }
    public function update(\App\Http\Requests\ExpenseTypes\UpdateExpenseTypeRequest $r, ExpenseType $expenseType):RedirectResponse { $this->repo->update($expenseType,$r->validated()); Inertia::flash('toast',['type'=>'success','message'=>__('Expense type updated.')]); return redirect()->route('expense-types.index'); }
    public function delete(ExpenseType $expenseType):Response { return Inertia::render('expense-types/delete',['expenseType'=>$expenseType]); }
    public function destroy(ExpenseType $expenseType):RedirectResponse { $this->repo->delete($expenseType); Inertia::flash('toast',['type'=>'success','message'=>__('Expense type deleted.')]); return redirect()->route('expense-types.index'); }
}
