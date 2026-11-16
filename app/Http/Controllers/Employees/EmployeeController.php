<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\Position;
use App\Repositories\Contracts\EmployeeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeRepository $employees)
    {
        //
    }

    /**
     * Display a paginated, searchable list of employees.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('employees/index', [
            'employees' => $this->employees->paginate($search !== '' ? $search : null),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): Response
    {
        return Inertia::render('employees/create', [
            ...$this->lookups(),
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->hasFile('photo')) {
            $data['image_path'] = $request->file('photo')->store('employees', 'public');
        }

        $this->employees->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee created.')]);

        return redirect()->route('employees.index');
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee): Response
    {
        return Inertia::render('employees/edit', [
            'employee' => $employee,
            ...$this->lookups(),
        ]);
    }

    /**
     * Update the specified employee.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);
        $oldImage = $employee->getRawOriginal('image_path');

        if ($request->hasFile('photo')) {
            $data['image_path'] = $request->file('photo')->store('employees', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $data['image_path'] = null;
        }

        $this->employees->update($employee, $data);

        // Only remove the old file after the record has been saved, so a
        // failed update cannot leave the employee pointing at a missing photo.
        if ($oldImage && ($request->hasFile('photo') || $request->boolean('remove_photo'))) {
            Storage::disk('public')->delete($oldImage);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee updated.')]);

        return redirect()->route('employees.index');
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        // The account is what delivers travel orders, so an employee with a
        // login cannot disappear from under it. The accounts module owns
        // unlinking, which keeps deletion here a single, explicit decision.
        if ($employee->user()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This employee has a login account. Remove the account link first.'),
            ]);

            return back();
        }

        if ($employee->image_path) {
            Storage::disk('public')->delete($employee->image_path);
        }

        $this->employees->delete($employee);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee deleted.')]);

        return back();
    }

    /**
     * The reference data every employee form needs.
     *
     * @return array{
     *     offices: Collection<int, Office>,
     *     positions: Collection<int, Position>,
     *     employmentTypes: Collection<int, EmploymentType>
     * }
     */
    private function lookups(): array
    {
        return [
            'offices' => Office::query()
                ->orderBy('office_name')
                ->get(['id', 'office_name']),
            'positions' => Position::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'employmentTypes' => EmploymentType::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
