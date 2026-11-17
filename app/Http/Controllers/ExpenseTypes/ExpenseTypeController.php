<?php

namespace App\Http\Controllers\ExpenseTypes;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseTypes\StoreExpenseTypeRequest;
use App\Http\Requests\ExpenseTypes\UpdateExpenseTypeRequest;
use App\Models\ExpenseType;
use App\Repositories\Contracts\ExpenseTypeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseTypeController extends Controller
{
    public function __construct(private readonly ExpenseTypeRepository $expenseTypes) {}

    public function index(Request $request): Response
    {
        return Inertia::render('expense-types/index', [
            'expenseTypes' => $this->expenseTypes->paginate($request->input('search')),
            'filters' => ['search' => $request->input('search') ?? ''],
        ]);
    }

    public function store(StoreExpenseTypeRequest $request): RedirectResponse
    {
        $this->expenseTypes->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense type created.')]);

        return redirect()->route('expense-types.index');
    }

    public function update(UpdateExpenseTypeRequest $request, ExpenseType $expenseType): RedirectResponse
    {
        $this->expenseTypes->update($expenseType, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense type updated.')]);

        return redirect()->route('expense-types.index');
    }

    public function destroy(ExpenseType $expenseType): RedirectResponse
    {
        $this->expenseTypes->delete($expenseType);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense type deleted.')]);

        return redirect()->route('expense-types.index');
    }
}
